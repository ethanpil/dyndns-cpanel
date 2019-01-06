# dyndns-cpanel

Dynamic DNS update script for CPanel accounts using `dyndns.com` API.

In essence it runs as a website php script that you can access to update the IP of an existing dns A record. You can host it on your cpanel webspace, probably as part of the main domain since cpanel will already have a valid ssl certificate for it.

Based on the [hard work](https://haanenterprises.com/2013/04/host-your-own-dynamic-dns-using-php-and-cpanel-apis/) of Mitchel Haan:

Changes to Mr. Haan's code:

* Removed the extra code that wasn't needed to make this an update only client
* Changed the get vars to comply with dyndns standards
* Added basic http authentication

## DYNDNS Compatibility Note

Most dyndns clients will work to update your cpanel using this script as the custom address. You will likely need to provide the FQDN as the hostname. For example:

```
hostname=remote.example.com
``` 

## Use:

Ensure you have an ssl cert on your website otherwise the username and password will be viewable across the Internet!  I did a `tcpdump` and loaded it into `wireshark` to confirm the username and password is not exposed when accessing via `https`.

```
curl -i https://username:password@website.com/dyndns.php?hostname=remote.domain.tld&myip=192.168.1.1
```

Note if `&myip=` is omitted the source IP is used (eg you don't need to work out your source IP before making the call).

## Setup

Update the variables at the top of the script to match your setting. Comments are provided to explain the values.

Script only updates existing dns A record entries; thus ensure they exist first.

Under certain server configurations `customonly` on line 209 must be set to 0 in order to find subdomain records. See [this](https://github.com/ethanpil/dyndns-cpanel/issues/3).

Copy it to your webhosted root.

Ensure php is enabled on your webhosted root (php 7.1 works fine).

Set permissions of the script to `600` (only `owner` can view it).

## Support

As is. No support provided.

## Thanks 
* Mitchel Haan for doing all the real work.
* @mihaiile for some security tweaks.
* @mattlyons0 for information on `customonly`
* @f03el for improved dyndns compliance
