#!/bin/bash
# This script will clean up old chroot directories, in case of mismatched libraries
apache2ctl stop
for iii in `df -k | grep none | cut -c50-`; do 
	SUB=`basename $iii`
	cd $iii
	cd ../..
	umount $iii
	cd subdomains/$SUB
	mkdir old_chroot
	mv selinux sys proc mnt home boot var usr srv root opt media dev lib64 sbin bin lib run etc cgi-bin old_chroot
done
/usr/share/dtc/admin/remount_aufs
apache2ctl start
