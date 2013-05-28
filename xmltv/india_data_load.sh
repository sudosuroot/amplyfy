#!/bin/bash


suffix=`date +"%Y-%m-%d"`
nextday=`date -d "1days" +"%Y-%m-%d"`
country='IN_airtel'
tv_cmd='/usr/bin/tv_grab_in --config-file /root/loader/india_tata.config --days 2 --output /mnt/India/'$country'_'$suffix'_'$nextday'.xml'
load_cmd='php /root/loader/india_airtel_listings_insert.php --country '$country' --xml /mnt/India/'$country'_'$suffix'_'$nextday'.xml'

echo $tv_cmd
`$tv_cmd 1> /mnt/logs/indiatv.out.$suffix 2> /mnt/logs/indiatv.err.$suffix`
echo $load_cmd
`$load_cmd 1> /mnt/logs/indiaload.out.$suffix 2> /mnt/logs/indiaload.err.$suffix`
