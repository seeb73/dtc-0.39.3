#!/bin/sh

set -e
#set -x

usage (){
	echo "Usage: $0 <destination-host> <username>"
}

get_my_opt () {
	if ! [ "${#}" = 2 ] ; then
		usage
		exit 1
	fi

	DST_HOST=$1
	DTC_USER=$2
}

get_remote_credentials () {
	echo "===> Getting login and password on remote"
	# Get a super user login / pass
	DST_ADM_LIST=`ssh ${DST_HOST} '/usr/share/dtc/admin/dtcpassadm -l | head -n 2 | tail -n1'`
	DST_LOGIN=`echo ${DST_ADM_LIST} | awk '{print $1}'`
	DST_PASS=`echo ${DST_ADM_LIST} | awk '{print $2}'`
}

copy_adm_login_and_client () {
	echo "===> Creating admin and client in remote server"

	admin_props="adm_pass path max_email max_ftp max_ssh quota bandwidth_per_month_mb expire prod_id allow_add_domain max_domain restricted_ftp_path allow_dns_and_mx_change allow_mailing_list_edit allow_subdomain_edit allow_cronjob_edit nbrdb resseller_flag ssh_login_flag ftp_login_flag pkg_install_flag last_used_lang"
	client_props="is_company company_name vat_num familyname christname addr1 addr2 addr3 city zipcode state country phone fax email special_note dollar disk_quota_mb bw_quota_per_month_gb expire customfld"

	# Get the client info
	CLIENT_ID=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT id_client FROM admin WHERE adm_login='${DTC_USER}'" | tail -n 1`
	Q="INSERT INTO clients ("
	V=") VALUES ("
	for i in ${client_props} ; do
		VAL=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT replace(${i} , '\'', ' ') FROM clients WHERE id='${CLIENT_ID}'" | tail -n 1`
		if ! [ "${i}" = "is_company" ] ; then
			Q=${Q}","
			V=${V}","
		fi
		Q=${Q}${i}
		V=${V}"'"${VAL}"'"
	done
	QUERY=${Q}${V}"); SELECT LAST_INSERT_ID();"

	# Insert that new record in the remote server and get the new client ID
	TMP=`mktemp`
	BASE_TMP=`basename ${TMP}`
	echo ${QUERY} >${TMP}
	# FIXME: we should use mktemp on the destination as well to avoid already existing files
	scp -q ${TMP} ${DST_HOST}:/tmp
	NEW_CLIENT_ID=`ssh ${DST_HOST} "mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc </tmp/${BASE_TMP}"`
	NEW_CLIENT_ID=`echo ${NEW_CLIENT_ID} | cut -d" " -f2`

	# Add the admin, linked to the client file
	Q="INSERT INTO admin (id_client,adm_login,shared_hosting_security"
	V=") VALUES ('${NEW_CLIENT_ID}','${DTC_USER}','sbox_aufs'"
	for i in ${admin_props} ; do
		VAL=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT ${i} FROM admin WHERE adm_login='${DTC_USER}'" | tail -n 1`	
		Q=${Q}","${i}
		V=${V}",'"${VAL}"'"
	done
	QUERY=${Q}${V}")"
	echo ${QUERY} >${TMP}
	scp -q ${TMP} ${DST_HOST}:/tmp
	ssh ${DST_HOST} "mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc </tmp/${BASE_TMP}"

	# Delete temp file on local and remote host and create the user folder.
	ssh ${DST_HOST} "rm /tmp/${BASE_TMP}"
	ssh ${DST_HOST} "mkdir -p /var/www/sites/${DTC_USER}"
	ssh ${DST_HOST} "chown dtc:dtcgrp /var/www/sites/${DTC_USER}"
	rm ${TMP}
}

export_adm_local_conf () {
	echo "===> Exporting admin config to remote server"
	# Get local web address for the DTC panel
	LOCAL_URL=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT administrative_site FROM config" | tail -n 1`
	# Get the export config of the admin
	LOCAL_adm_pass=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT adm_pass FROM admin WHERE adm_login='${DTC_USER}'" | tail -n 1`
	TMP=`mktemp`
	curl --insecure "https://${LOCAL_URL}/dtc/?adm_login=${DTC_USER}&adm_pass=${LOCAL_adm_pass}&action=export_my_account&addrlink=myaccount" >${TMP}

	# Now, send it to destination
	curl --silent --insecure -o /dev/null -F domain_import_file=@${TMP} -F MAX_FILE_SIZE=30000000 -F rub=adminedit -F action=import_domain -F adm_login=${DTC_USER} -F adm_pass=${LOCAL_adm_pass} \
		--user ${DST_LOGIN}:${DST_PASS} https://${DST_HOST}/dtcadmin/
	rm ${TMP}
}

export_adm_dbs () {
	echo "===> Exporting MySQL databases"
	NBR_USERS=`mysql --defaults-file=/etc/mysql/debian.cnf -Dmysql --execute="SELECT DISTINCT(User) FROM user WHERE dtcowner='${DTC_USER}'" | wc -l`
	NBR_USERS=$((${NBR_USERS} - 1 ))
	SQL_USERS=`mysql --defaults-file=/etc/mysql/debian.cnf -Dmysql --execute="SELECT DISTINCT(User) FROM user WHERE dtcowner='${DTC_USER}'" | tail -n ${NBR_USERS}`
	for i in ${SQL_USERS} ; do
		NBR_DBS=`mysql --defaults-file=/etc/mysql/debian.cnf -Dmysql --execute="SELECT DISTINCT(Db) FROM db WHERE User='${i}'" | wc -l`
		NBR_DBS=$((${NBR_DBS} - 1 ))
		SQL_DBS=`mysql --defaults-file=/etc/mysql/debian.cnf -Dmysql --execute="SELECT DISTINCT(Db) FROM db WHERE User='${i}'" | tail -n ${NBR_DBS}`
		for j in ${SQL_DBS} ; do
			echo "-> Exporting db: ${j}"
			TMP=`mktemp`
			mysqldump --defaults-file=/etc/mysql/debian.cnf -c --add-drop-table --databases ${j} >${TMP}
			echo "-> SCP to destination"
			scp ${TMP} ${DST_HOST}:/tmp
			echo "-> Importing"
			# FIXME: we should use mktemp on the destination as well
			ssh ${DST_HOST} "mysql --defaults-file=/etc/mysql/debian.cnf <${TMP}"
			rm ${TMP}
			ssh ${DST_HOST} "rm ${TMP}"
		done
	done
}

rsync_all_files () {
	NBR_ENTRY=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT name FROM domain WHERE owner='${DTC_USER}'" | wc -l`
	NBR_ENTRY=$(($NBR_ENTRY - 1 ))
	DOMAINS=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT name FROM domain WHERE owner='${DTC_USER}'" | tail -n ${NBR_ENTRY} | tr \\\r\\\n ,\ `
	echo "Domains are: ${DOMAINS}"
	for D in ${DOMAINS} ; do
		echo "===> Doing rsync of ${D}"
		ssh ${DST_HOST} "mkdir -p /var/www/sites/${DTC_USER}/${D}/Mailboxs /var/www/sites/${DTC_USER}/${D}/subdomains"

		# Copy of all mailboxes
		if [ -d /var/www/sites/${DTC_USER}/${D}/Mailboxs ] ; then
			nice rsync -e ssh -azvp /var/www/sites/${DTC_USER}/${D}/Mailboxs/ ${DST_HOST}:/var/www/sites/${DTC_USER}/${D}/Mailboxs
		fi

		# Copy of all subdomains
		for i in /var/www/sites/${DTC_USER}/${D}/subdomains/* ; do
			S=`basename ${i}`
			ssh ${DST_HOST} "mkdir -p /var/www/sites/${DTC_USER}/${D}/subdomains/${S}/html /var/www/sites/${DTC_USER}/${D}/subdomains/${S}/logs"
			if [ -d /var/www/sites/${DTC_USER}/${D}/subdomains/${S}/html ] ; then
				nice rsync --delete -e ssh -azvp /var/www/sites/${DTC_USER}/${D}/subdomains/${S}/html/ ${DST_HOST}:/var/www/sites/${DTC_USER}/${D}/subdomains/${S}/html
			fi
			if [ -d /var/www/sites/${DTC_USER}/${D}/subdomains/${S}/logs ] ; then
				nice rsync -e ssh -azvp /var/www/sites/${DTC_USER}/${D}/subdomains/${S}/logs/ ${DST_HOST}:/var/www/sites/${DTC_USER}/${D}/subdomains/${S}/logs
			fi
		done

		# Copy of all lists
		if [ -d /var/www/sites/${DTC_USER}/${D}/lists ] ; then
			ssh ${DST_HOST} "mkdir -p /var/www/sites/${DTC_USER}/${D}/lists"
			nice rsync -e ssh -azvp /var/www/sites/${DTC_USER}/${D}/lists/ ${DST_HOST}:/var/www/sites/${DTC_USER}/${D}/lists
		fi
	done
}




fix_php_rights_cleanup_and_db_to_localhost () {
	NBR_ENTRY=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT name FROM domain WHERE owner='${DTC_USER}'" | wc -l`
	NBR_ENTRY=$(($NBR_ENTRY - 1 ))
	DOMAINS=`mysql --defaults-file=/etc/mysql/debian.cnf -Ddtc --execute="SELECT name FROM domain WHERE owner='${DTC_USER}'" | tail -n ${NBR_ENTRY} | tr \\\r\\\n ,\ `
	for D in ${DOMAINS} ; do
		echo "===> Fixing .php unix rights"
		ssh ${DST_HOST} "find /var/www/sites/${DTC_USER}/${D}/subdomains -iname '*.php' -exec chmod +x {} \;"
		echo "===> Switching from localhost to 127.0.0.1"
		ssh ${DST_HOST} "find /var/www/sites/${DTC_USER}/${D}/subdomains -iname '*.php' -exec sed -i s/localhost/127.0.0.1/ {} \;"
		echo "===> Cleaning old chroot copy"
		CLEANUP_FOLDERS="boot home media mnt opt proc root selinux srv sys usr bin dev etc lib lib64 libexec sbin var"
		ssh ${DST_HOST} "for i in /var/www/sites/${DTC_USER}/${D}/subdomains/* ; do cd \$i ; rm -rf ${CLEANUP_FOLDERS} ; done"
	done
}

get_my_opt $@
get_remote_credentials
copy_adm_login_and_client
export_adm_local_conf
export_adm_dbs
rsync_all_files
fix_php_rights_cleanup_and_db_to_localhost

exit 0
