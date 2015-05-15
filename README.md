#dyndns-cpanel

Dynamic DNS update script for CPanel accounts using DYNDNS.com API

Based on the hard work of Mitchel Haan:

https://haanenterprises.com/2013/04/host-your-own-dynamic-dns-using-php-and-cpanel-apis/

Changes to Mr. Haan's code:

* Removed the extra code that wasn't needed to make this an update only client
* Changed the get vars to comply with dyndns standards
* Added basic http authentication

##DYNDNS Compatibility Note
Most dyndns clients will work to update your cpanel using this script as the custom address. You will likely need to only provide the subdomain and not the full address as the hostname:

(ie: with this script, `hostname=remote`  instead of `hostname=remote.example.com`

##Use:
`http://username:password@website.com/dyndns.php?hostname=remote&myip=192.168.1.1`

##Setup
Update the variables at the top of the script to match your setting. Comments are provided to explain the values.

Under certain server configurations `customonly` on line 209 must be set to 0 in order to find subdomain records. See https://github.com/ethanpil/dyndns-cpanel/issues/3

##Support
As is. No support provided.

##Thanks 
* Mitchel Haan for doing all the real work.
* @mihaiile for some security tweaks.
* @mattlyons0 for information on `customonly`

