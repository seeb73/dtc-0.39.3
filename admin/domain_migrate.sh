#!/bin/sh

set -e
#set -x

usage (){
	echo "Usage: $0 <destination-host> <domain> <destination-admin>"
}

get_my_opt () {
	if ! [ "${#}" = 3 ] ; then
		usage
		exit 1
	fi

	DST_HOST=$1
	SRC_DOMAIN=$2
	DST_ADMIN=$3
}

get_remote_credentials () {
	echo "===> Getting login and password on remote"
	# Get a super user login / pass
	DST_ADM_LIST=`ssh ${DST_HOST} '/usr/share/dtc/admin/dtcpassadm -l | head -n 2 | tail -n1'`
	DST_LOGIN=`echo ${DST_ADM_LIST} | awk '{print $1}'`
	DST_PASS=`echo ${DST_ADM_LIST} | awk '{print $2}'`
	# Get the destination admin pass
	DST_ADM_PASS=`ssh ${DST_HOST} "mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute=\"SELECT adm_pass FROM admin WHERE adm_login='${DST_ADMIN}'\" | tail -n1"`
}

get_local_admin_name () {
	echo "===> Getting local admin name"
	SRC_ADM_LOGIN=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT owner FROM domain WHERE name='${SRC_DOMAIN}'" | tail -n 1`
}

export_domain_local_conf () {
	echo "===> Exporting admin config to remote server"
	# Get local web address for the DTC panel
	LOCAL_URL=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT administrative_site FROM config" | tail -n 1`
	# Get the export config of the admin
	LOCAL_adm_pass=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT adm_pass FROM admin WHERE adm_login='${SRC_ADM_LOGIN}'" | tail -n 1`
	TMP=`mktemp`
	curl "https://${LOCAL_URL}/dtc/?adm_login=${SRC_ADM_LOGIN}&adm_pass=${LOCAL_adm_pass}&action=export_domain&addrlink=${SRC_DOMAIN}" >${TMP}

	# Now, send it to destination
	curl --silent --insecure -o /dev/null -F domain_import_file=@${TMP} -F MAX_FILE_SIZE=30000000 -F rub=adminedit -F action=import_domain -F adm_login=${DST_ADMIN} -F adm_pass=${DST_ADM_PASS} \
		--user ${DST_LOGIN}:${DST_PASS} https://${DST_HOST}/dtcadmin/
	rm ${TMP}
}

rsync_all_files () {
	nice rsync -e ssh -azvp /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/ ${DST_HOST}:/var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}
}

fix_php_rights_cleanup_and_db_to_localhost () {
	echo "===> Fixing .php unix rights"
	ssh ${DST_HOST} "find /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains -iname '*.php' -exec chmod +x {} \;"
	echo "===> Switching from localhost to 127.0.0.1"
	ssh ${DST_HOST} "find /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains -iname '*.php' -exec sed -i s/localhost/127.0.0.1/ {} \;"
	echo "===> Cleaning old chroot copy"
	CLEANUP_FOLDERS="bin dev etc lib lib64 libexec sbin var usr/bin usr/libexec usr/share usr/lib/zoneinfo"
	ssh ${DST_HOST} "for i in ${CLEANUP_FOLDERS} ; do -rf /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains/*/${i} ; done"
}

get_my_opt $@
get_remote_credentials
get_local_admin_name
export_domain_local_conf
rsync_all_files
fix_php_rights_cleanup_and_db_to_localhost

exit 0
