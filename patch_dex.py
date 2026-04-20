#!/usr/bin/env python3
"""
classes2.dex 补丁工具 - PHP 虚拟主机版
将 DEX 文件中的 https://zq.likeuuu.top/ 替换为你的域名

用法:
  python3 patch_dex.py <classes2.dex> <你的域名URL> [输出文件]
  
示例:
  python3 patch_dex.py classes2.dex https://zq.abc.com/ patched.dex
  python3 patch_dex.py classes2.dex http://api.abc.com/ patched.dex
"""

import sys
import os
import struct
import zlib

OLD_URL = b'https://zq.likeuuu.top/'

def patch_dex(input_path, new_base_url, output_path=None):
    if output_path is None:
        base, ext = os.path.splitext(input_path)
        output_path = f"{base}_patched{ext}"
    
    if not new_base_url.endswith('/'):
        new_base_url += '/'
    
    new_url = new_base_url.encode('utf-8')
    
    # 原始 URL 长度 23 字节
    if len(new_url) > len(OLD_URL):
        print(f"❌ 新 URL 长度 ({len(new_url)}) 超过原 URL 长度 ({len(OLD_URL)})")
        print(f"\n📐 长度参考 (≤ 23 字节可用):")
        examples = [
            'https://zq.abc.com/',
            'https://api.abc.cn/',
            'http://api.abc.com/',
            'https://x.abcdef.com/',
        ]
        for ex in examples:
            ok = '✓' if len(ex.encode()) <= len(OLD_URL) else '✗'
            print(f"  {ex:30s} = {len(ex.encode()):2d} bytes {ok}")
        print(f"\n💡 建议使用短子域名，如 https://zq.你的域名.com/")
        return False
    
    padded_url = new_url.ljust(len(OLD_URL), b'\x00')
    
    with open(input_path, 'rb') as f:
        data = bytearray(f.read())
    
    if data[:3] != b'dex':
        print(f"❌ 不是有效的 DEX 文件")
        return False
    
    count = 0
    pos = 0
    while True:
        idx = data.find(OLD_URL, pos)
        if idx == -1:
            break
        data[idx:idx + len(OLD_URL)] = padded_url
        count += 1
        print(f"  替换 #{count}: offset 0x{idx:x}")
        pos = idx + len(OLD_URL)
    
    if count == 0:
        print(f"❌ 未找到原始 URL")
        return False
    
    # 重新计算 checksum
    new_checksum = zlib.adler32(bytes(data[12:])) & 0xFFFFFFFF
    data[8:12] = struct.pack('<I', new_checksum)
    
    with open(output_path, 'wb') as f:
        f.write(data)
    
    print(f"\n✅ 成功! 共替换 {count} 处")
    print(f"   原始: {OLD_URL.decode()}")
    print(f"   替换: {new_base_url}")
    print(f"   输出: {output_path}")
    print(f"\n⚠️  需要重新签名 APK 后才能安装")
    return True

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print(__doc__)
        sys.exit(1)
    success = patch_dex(sys.argv[1], sys.argv[2], sys.argv[3] if len(sys.argv) > 3 else None)
    sys.exit(0 if success else 1)
