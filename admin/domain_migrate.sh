#!/bin/sh

set -e

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
	TMP_EXPORT=`mktemp -t DTC_domain_migrate_export.XXXXXXXXXXXX`
	curl "https://${LOCAL_URL}/dtc/?adm_login=${SRC_ADM_LOGIN}&adm_pass=${LOCAL_adm_pass}&action=export_domain&addrlink=${SRC_DOMAIN}" >${TMP_EXPORT}

	# Now, send it to destination
	curl --silent --insecure -o /dev/null -F domain_import_file=@${TMP_EXPORT} -F MAX_FILE_SIZE=30000000 -F rub=adminedit -F action=import_domain -F adm_login=${DST_ADMIN} -F adm_pass=${DST_ADM_PASS} \
		--user ${DST_LOGIN}:${DST_PASS} https://${DST_HOST}/dtcadmin/
	rm ${TMP_EXPORT}
}

rsync_all_files () {
	# Copy of all mailboxes
	ssh ${DST_HOST} "mkdir -p /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/Mailboxs"
	nice rsync -e ssh -azvp /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/Mailboxs/ ${DST_HOST}:/var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/Mailboxs
	# Copy of all subdomains
	for i in /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/subdomains/* ; do
		SUBDOMAIN=`basename ${i}`
		ssh ${DST_HOST} "mkdir -p /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains/${SUBDOMAIN}/html /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains/${SUBDOMAIN}/logs"
		if [ -d /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/subdomains/${SUBDOMAIN}/html ] ; then
			nice rsync --delete -e ssh -azvp /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/subdomains/${SUBDOMAIN}/html/ ${DST_HOST}:/var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains/${SUBDOMAIN}/html
		fi
		if [ -d /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/subdomains/${SUBDOMAIN}/logs ] ; then
			nice rsync -e ssh -azvp /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/subdomains/${SUBDOMAIN}/logs/ ${DST_HOST}:/var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains/${SUBDOMAIN}/logs
		fi
	done
	# Copy of all lists
	if [ -d /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/lists ] ; then
		ssh ${DST_HOST} "mkdir -p /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/lists"
		nice rsync -e ssh -azvp /var/www/sites/${SRC_ADM_LOGIN}/${SRC_DOMAIN}/lists/ ${DST_HOST}:/var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/lists
	fi
}

fix_php_rights_cleanup_and_db_to_localhost () {
	echo "===> Fixing .php unix rights"
	ssh ${DST_HOST} "find /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains -iname '*.php' -exec chmod +x {} \;"
	echo "===> Switching from localhost to 127.0.0.1"
	ssh ${DST_HOST} "find /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains -iname '*.php' -exec sed -i s/localhost/127.0.0.1/ {} \;"
	echo "===> Cleaning old chroot copy"
	CLEANUP_FOLDERS="boot home media mnt opt proc root selinux srv sys usr bin dev etc lib lib64 libexec sbin var"
	ssh ${DST_HOST} "for i in /var/www/sites/${DST_ADMIN}/${SRC_DOMAIN}/subdomains/* ; do cd \$i ; rm -rf ${CLEANUP_FOLDERS} ; done"
}

get_my_opt $@
get_remote_credentials
get_local_admin_name
export_domain_local_conf
rsync_all_files
fix_php_rights_cleanup_and_db_to_localhost

exit 0
