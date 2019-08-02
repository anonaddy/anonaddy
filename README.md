# Anonymous Email Forwarding

This is the source code for [app.anonaddy.com](https://app.anonaddy.com).

## FAQ

#### **Why did you make this site?**

I made this service after trying a few other options that do a similar thing. I was really interested in how they worked and loved the thought of protecting my real email addresses from spam.

I also wanted to address some issues with other services such as:

* Proprietary closed source code
* Adverts, analytics and trackers on the sites
* No option to encrypt emails using a GPG/OpenPGP key
* No option for multiple recipients

I made the code open-source to show everyone what was going on behind the scenes and to allow others to help improve the application.

I use this service myself for the vast majority of sites I'm signed up to.


#### **Do you store emails?**

No I definitely do not store/save any emails that pass through the server.


#### **Can I use my own domain?**

Yes you can use your own domain name so you can also have *@yourdomain.com as your aliases. To do so you simply need to add an MX record to your domain so that our server can handle incoming emails.


#### **Why should I use this instead of a similar service?**

Here are a few reasons I can think of:

* Bring your own GPG/OpenPGP key to encrypt your forwarded emails (and replace subjects)
* No adverts
* No analytics or trackers (just server access logs)
* No third party content
* Open-source application code
* No limitation on the number of aliases that can be created
* Generous monthly bandwidth
* Multiple domains to choose for aliases (currently anonaddy.com and anonaddy.me)
* Ability to generate UUID aliases
* New features added regularly


#### **How do I add my own GPG/OpenPGP key for encryption?**

On the recipients page you simply need to click "Add public key" and paste in your **public** key data. Now all emails forwarded to you will be encrypted with your key. You should also replace the subject line of forwarded messages in your account settings as this cannot be encrypted.

#### **Are attachments encrypted too?**

Yes attachments are part of the email body and are also encrypted if you have it enabled.

#### **Are forwarded emails signed when encrption is enabled?**

Yes when you have encryption enabled all forwarded emails are signed using our mailer@anonaddy.me private key.

You can add this key to your own keyring so that you can verify emails have come from us.

The fingerprint of the mailer@anonaddy.me key is "26A987650243B28802524E2F809FD0D502E2F695" you can find the key on [https://keys.openpgp.org](https://keys.openpgp.org/search?q=26A987650243B28802524E2F809FD0D502E2F695).


#### **What if I don't trust you?**

It's good to keep your guard up when online so you should never trust anyone 100%. I'll try my best to be as honest and transparent as I can but if you still aren't convinced you can always just fire up your own server and self-host this application. I'll be adding more details on how to do this soon.


#### **What is the maximum number of recipients I can add to an alias?**

The limit is currently set to 10 which should suffice in the vast majority of situations.


#### **What happens when I delete my account?**

When you delete your account the following happens:

* All of your recipients are deleted from the database
* All of your aliases are deleted from the database (aliases with a custom domain are soft deleted - see why below)
* All of your custom domains are deleted from the database
* Your user details are deleted from the database
* Your username is encrypted and added to a table in the database. This is to prevent anybody signing up with the same username in the future.

The reason aliases with a custom domain are soft deleted (a deleted_at column is filled in the database) is to ensure that nobody else can register your same domain in the future and then sign up to our site and receive emails for aliases you have previously used.


#### **Does this work with any email provider?**

Yes this will work with any provider, althought I can't guarantee it won't land in spam initially.


#### **Will people see my real email if I reply to a forwarded one?**

No, your real email will not be shown, the email will look as if it has come from us instead. Just make sure not to include anything that might identify you when composing the reply, i.e. your full name.


#### **Can emails have attachments?**

Yes you can add attachments to emails forwarded and replies. Attachments do count towards your bandwidth.


#### **What is the max email size limit?**

The max email size is currently set to 10MB.


#### **How do you prevent spammers?**

The following is in place to help prevent spam:

* SpamAssassin - score threshold of 5.0
* DNS blacklist checks - spamhaus.org
* SPF, DKIM - to check the SPF record on the sender's domain
* Disposable Email Addresses are blocked - [disposable-email-domains](https://github.com/ivolo/disposable-email-domains)
* DMARC - to check for email spoofing and reject emails that fail
* FQDN - the sender must be using a valid fully qualified domain name
* PTR record check - if the sender has no valid PTR record it is rejected

#### **What do you use to do DNS lookups on domain names?**

The server is running a local DNS caching server to improve the speed of queries. Cloudflare's (1.1.1.1) is used as a fallback.


#### **Is there a limit to how many emails I can forward?**

Not unless you are really going to town. Each user is throttled to 200 emails per hour through the server.

#### **Is there a limit to how many aliases I can create?**

Currently you are limited to creating 10 new aliases per hour. If you try to create more than this the emails will be deferred until you are back below the limit.

#### **How is my bandwidth calculated?**

Each time a new email is received Postfix calculates its size in bytes, a column in our database is then simply incremented by the size if the email is forwarded or replied. At the start of each month your bandwidth is reset to 0.

I don't use rolling 30 day total as the only way to do this would be to log the date and size of every single email received.

Blocked emails do not count towards your bandwidth (e.g. an alias is inactive or deleted).


#### **What happens if I go over my bandwidth limit in a given month?**

If you get close to your limit you'll be sent an email letting you know. If you continue and go over your limit the server will start discarding emails until your bandwidth resets the next month.


#### **I'm not receiving any emails, what's wrong?**

Please make sure to add mailer@anonaddy.me to your address book and check your spam folder. Make sure to mark emails from us as safe if they turn up in spam. If you still aren't receiving emails contact me.


#### **How do I know this site won't disappear next month?**

I am very passionite about this project. I use it myself everyday and will definitely be keeping it running.


#### **Is the application tested?**

Yes it has automated PHPUnit tests written.


#### **How do I host this myself?**

You will need to set up your own server with Postfix so that you can pipe the received mail to the application. I'll add more details and instructions here soon.

#### **Who's behind AnonAddy?**

My name is Will Browning, I'm a web developer from the UK and an advocate for online privacy and open-source software. You can find me on [Twitter](https://twitter.com/willbrowningme) although I don't tweet that much!


#### **I couldn't find an answer to my question, how can I contact you?**

For any others questions just send an email to - [contact@anonaddy.com](mailto:contact@anonaddy.com)

## Self Hosting

#### Prerequisites

* Postfix (2.7)+
* PHP (7.1+) and the [php-mailparse](https://pecl.php.net/package/mailparse) extension plus the [php-gnupg](https://pecl.php.net/package/gnupg) extension if you plan to encrypt forwarded emails
* Port 25 unblocked and open
* Redis (4.x+) not required if you don't plan to use it for throttling or queues
* FQDN as hostname e.g. mail.anonaddy.me
* MariaDB / MySQL (or any other database compatible with Laravel)
* Nginx
* SpamAssassin, Amavis, OpenDKIM, OpenDMARC, postfix-policyd-spf-python
* DNS records - MX, SPF, DKIM, DMARC
* Reverse DNS
* SSL/TLS Encryption - you can install a free certificate from Let’s Encrypt.

#### Postfix configuration

In /etc/postfix/master.cf you need to make sure it has this at the top:

```cf
smtp       inet  n       -       -       -       -       smtpd
        -o content_filter=anonaddy:dummy
```

This should be the only line for smtp.

Then add this these lines to the bottom:

```cf
anonaddy unix - n n - - pipe
  flags=F user=youruser argv=php /path/to/your/webapp/artisan anonaddy:receive-email --sender=${sender} --recipient=${recipient} --local_part=${user} --extension=${extension} --domain=${domain} --size=${size}
```

Making sure to replace `youruser` with the username of the user who will run the artisan command and also to update the /path to your installation.

This is what will pipe the email through to our applicaton so we can determine who the alias belongs to and who to forward the email to.

More instructions to follow soon...

## Thanks

Thanks to [https://gitlab.com/mailcare/mailcare](https://gitlab.com/mailcare/mailcare) and [https://github.com/niftylettuce/forward-email](https://github.com/niftylettuce/forward-email) for their awesome open-source projects that helped me along the way.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.