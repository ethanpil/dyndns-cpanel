#dyndns-cpanel

Dynamic DNS update script for CPanel accounts with DYNDNS.com API

Based on the hard work of Mitchel Haan
https://haanenterprises.com/2013/04/host-your-own-dynamic-dns-using-php-and-cpanel-apis/

Most dyndns clients will work to update your cpanel using this script as the custom address. You will likely need to only provide the subdomain and not the full address as the hostname:

(ie: with this script, `hostname=remote`  instead of `hostname=remote.example.com`

##Use:
`http://username:password@website.com/dyndns.php?hostname=remote&myip=192.168.1.1`

##Setup
Update the variables at the top of the script to match your setting. Comments are provided to explain the values.

##Support
As is. No support provided.

