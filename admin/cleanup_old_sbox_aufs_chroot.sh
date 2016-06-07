#!/bin/bash
# This script will cleanup old lib data in chroot, which might conflict with the aufs mounted lib
for i in `df -k | grep none | cut -c91- 2>/dev/null`; do
	SUB=`basename $i`
	if [ -e $i/../../subdomains/$SUB/lib ]; then
		umount $i
		rm -rf $i/../../subdomains/$SUB/var
		rm -rf $i/../../subdomains/$SUB/usr
		rm -rf $i/../../subdomains/$SUB/sbin
		rm -rf $i/../../subdomains/$SUB/libexec
		rm -rf $i/../../subdomains/$SUB/dev
		rm -rf $i/../../subdomains/$SUB/bin
		rm -rf $i/../../subdomains/$SUB/cgi-bin
		rm -rf $i/../../subdomains/$SUB/lib64
		rm -rf $i/../../subdomains/$SUB/etc
		/usr/share/dtc/admin/remount_aufs
	fi
done
