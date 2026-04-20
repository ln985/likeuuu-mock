#!/usr/bin/env python3
"""
classes2.dex 补丁工具
将 DEX 文件中的 https://zq.likeuuu.top/ 替换为自定义服务器地址

用法:
  python3 patch_dex.py <classes2.dex> <new_base_url> [output.dex]
  
示例:
  python3 patch_dex.py classes2.dex http://192.168.1.100:8080/
  python3 patch_dex.py classes2.dex https://my-server.com/ patched.dex
"""

import sys
import os
import struct

OLD_URL = b'https://zq.likeuuu.top/'

def patch_dex(input_path, new_base_url, output_path=None):
    if output_path is None:
        base, ext = os.path.splitext(input_path)
        output_path = f"{base}_patched{ext}"
    
    # 确保新 URL 以 / 结尾
    if not new_base_url.endswith('/'):
        new_base_url += '/'
    
    new_url = new_base_url.encode('utf-8')
    
    # DEX 文件中的 URL 必须和原 URL 等长或更短
    # 因为 DEX 的 string_id 包含长度信息
    # 原始 URL: https://zq.likeuuu.top/ = 23 字节
    if len(new_url) > len(OLD_URL):
        print(f"错误: 新 URL 长度 ({len(new_url)}) 超过原 URL 长度 ({len(OLD_URL)})")
        print(f"请使用不超过 {len(OLD_URL)} 字节的 URL")
        print(f"")
        print(f"可用示例 (均 ≤ 23 字节):")
        print(f"  http://192.168.1.1:80/       = {len(b'http://192.168.1.1:80/')} bytes ✓")
        print(f"  http://10.0.0.1:8080/        = {len(b'http://10.0.0.1:8080/')} bytes ✓")
        print(f"  http://127.0.0.1:8080/       = {len(b'http://127.0.0.1:8080/')} bytes ✓")
        print(f"  http://localhost:80/         = {len(b'http://localhost:80/')} bytes ✓")
        print(f"  https://zq.mydomain.com/    = {len(b'https://zq.mydomain.com/')} bytes ✓")
        print(f"  http://192.168.1.100:8080/   = {len(b'http://192.168.1.100:8080/')} bytes ✗ (超长)")
        return False
    
    # 如果更短，用 null 字节填充到相同长度
    padded_url = new_url.ljust(len(OLD_URL), b'\x00')
    
    with open(input_path, 'rb') as f:
        data = bytearray(f.read())
    
    # 验证 DEX 文件头
    if data[:3] != b'dex':
        print(f"错误: {input_path} 不是有效的 DEX 文件")
        return False
    
    # 搜索并替换
    count = 0
    pos = 0
    while True:
        idx = data.find(OLD_URL, pos)
        if idx == -1:
            break
        data[idx:idx + len(OLD_URL)] = padded_url
        count += 1
        print(f"  替换位置 #{count}: offset 0x{idx:x}")
        pos = idx + len(OLD_URL)
    
    if count == 0:
        print(f"未找到原始 URL: {OLD_URL.decode()}")
        return False
    
    # 重新计算 DEX checksum (adler32)
    import zlib
    # DEX checksum 从第 12 字节开始计算 (跳过 magic + checksum + signature)
    checksum_data = bytes(data[12:])
    new_checksum = zlib.adler32(checksum_data) & 0xFFFFFFFF
    data[8:12] = struct.pack('<I', new_checksum)
    
    with open(output_path, 'wb') as f:
        f.write(data)
    
    print(f"\n成功! 共替换 {count} 处 URL")
    print(f"原始: {OLD_URL.decode()}")
    print(f"替换: {new_base_url}")
    print(f"输出: {output_path}")
    print(f"\n注意: SHA-1 签名未更新, 需要重新签名 APK")
    return True

def main():
    if len(sys.argv) < 3:
        print(__doc__)
        sys.exit(1)
    
    input_path = sys.argv[1]
    new_url = sys.argv[2]
    output_path = sys.argv[3] if len(sys.argv) > 3 else None
    
    if not os.path.exists(input_path):
        print(f"错误: 文件不存在: {input_path}")
        sys.exit(1)
    
    success = patch_dex(input_path, new_url, output_path)
    sys.exit(0 if success else 1)

if __name__ == '__main__':
    main()
