#!/bin/sh

#
# migrate-mailman-list-to-dtc.sh
#
# script to help migrate mailman lists to dtc, tested with V0.35.5 R1
#
# Copyright 2012 Jesse Norell <jesse@kci.net>
# Copyright 2012 Kentec Communications, Inc.
# 
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
#     Unless required by applicable law or agreed to in writing, software
#     distributed under the License is distributed on an "AS IS" BASIS,
#     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#     See the License for the specific language governing permissions and
#     limitations under the License.
#

#
# you MUST edit these variables for any hope of this working!!
#

# dtc server url
DTC=https://dtc.yourdomain.com/dtc/

# dtc username (ie. the customer's admin username)
ADMIN=customer

# dtc password (ie. the customer's admin password)
ADM_PASS=lousypassWord

# domain to migrate list to (should already exist in customer's admin account)
DOM=customer.com

# which mailman lists to migrate (these are names of a lists sub-directory)
# note our old mailman server created lists named username_listname,
# the username_ part will get stripped out below
LISTS="olduser_list1 olduser_list2 olduser_*"

# mailmain lists directory
DIR=/var/lib/mailman/lists

# command to dump the mailman config 
DUMP_CONFIG="/usr/lib/mailman/bin/dumpdb config.pck"


# base curl command 
CURL="curl --silent --insecure -o /dev/null -F adm_login=${ADMIN} -F adm_pass=${ADM_PASS} \
    -F addrlink=${DOM}/mailing-lists -F edit_domain=${DOM} -F whatdoiedit=mails "

cd ${DIR} || ( echo "directory (${DIR}) not found" 1>&2; exit 1)

for l in ${LISTS}
do
    cd ${l}
    list=`echo ${l} | cut -d_ -f2`

    echo list: ${list}

    owner=`${DUMP_CONFIG} | grep "'owner': " | cut -d\[ -f2 | cut -d\' -f2 | cut -d\' -f1`

    # create the list
    ${CURL} -F newlist_name=${list} -F newlist_owner=${owner} -F addnewlisttodomain=Ok ${DTC}

    # set some list options
    ${CURL} -F edit_mailbox=${list} -F editmail_owner=${owner} -F closedlist=yes \
        -F 'owner[]'=${owner} -F 'owner[]'= -F 'moderators[]'= -F prefix='['${list}']' \
        -F 'delheaders[]'= -F customheaders= -F footer= -F noarchive=yes -F subonlyget=yes \
        -F digestinterval= -F digestmaxmails= -F notifysub=yes -F memorymailsize= -F relayhost= \
        -F verp= -F maxverprecips= -F delimiter= -F bouncelife= -F access= -F rcfile= \
        -F modifylistdata=Ok ${DTC}

    # add list subscribers
    (${DUMP_CONFIG} | perl -e '$m="";$stop=$in=0; while (<>) { last if $stop; m/'\'members\'':/ && {$in=1}; $in && {$m .= $_};  m/\}/ && {$stop=1 && $in=0}; } print "$m\n";') \
    | while read line
    do
        if [ -z "${line}" ]; then
            continue
        fi

        sub=`echo $line | cut -d\{ -f2 | cut -d\} -f1 | cut -d\' -f2 | cut -d\' -f1`
        if [ -n "`echo ${sub} | grep @`" ]; then
            ${CURL} -F edit_mailbox=${list} -F action=subscribe_new_user -F subscriber_email=${sub} ${DTC}
        fi

    done

    cd ${DIR}
done

exit
