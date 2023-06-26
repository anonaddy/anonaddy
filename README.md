# Anonymous Email Forwarding

This is the source code for self-hosting AnonAddy.

## FAQ

- [Why is it called AnonAddy?](#why-is-it-called-anonaddy)
- [Why did you make this site?](#why-did-you-make-this-site)
- [Why should I use AnonAddy?](#why-should-i-use-anonaddy)
- [Do you store emails?](#do-you-store-emails)
- [What is a shared domain alias?](#what-is-a-shared-domain-alias)
- [What is a standard alias?](#what-is-a-standard-alias)
- [Can I use my own domain?](#can-i-use-my-own-domain)
- [Can I add a domain and also use it as a recipient?](#can-i-add-a-domain-and-also-use-it-as-a-recipient)
- [Can I add a domain if I'm already using it for email somewhere else?](#can-i-add-a-domain-if-im-already-using-it-for-email-somewhere-else)
- [Why should I use this instead of a similar service?](#why-should-i-use-this-instead-of-a-similar-service)
- [Is there a browser extension?](#is-there-a-browser-extension)
- [Is there an Android app?](#is-there-an-android-app)
- [Is there an iOS app?](#is-there-an-ios-app)
- [Is there a Raycast extension?](#is-there-a-raycast-extension)
- [How do I add my own GPG/OpenPGP key for encryption?](#how-do-i-add-my-own-gpgopenpgp-key-for-encryption)
- [Are attachments encrypted too?](#are-attachments-encrypted-too)
- [Are forwarded emails signed when encryption is enabled?](#are-forwarded-emails-signed-when-encryption-is-enabled)
- [What if I don't want anyone to link ownership of my aliases together?](#what-if-i-dont-want-anyone-to-link-ownership-of-my-aliases-together)
- [Where is the server located?](#where-is-the-server-located)
- [What if I don't trust you?](#what-if-i-dont-trust-you)
- [What is the maximum number of recipients I can add to an alias?](#what-is-the-maximum-number-of-recipients-i-can-add-to-an-alias)
- [What happens when I delete my account?](#what-happens-when-i-delete-my-account)
- [Does this work with any email provider?](#does-this-work-with-any-email-provider)
- [How do I reply to a forwarded email?](#how-do-i-reply-to-a-forwarded-email)
- [I'm trying to reply/send from an alias but the email keeps coming back to me, what's wrong?](#im-trying-to-replysend-from-an-alias-but-the-email-keeps-coming-back-to-me-whats-wrong)
- [I'm trying to reply/send from an alias but it is rejected, what's wrong?](#im-trying-to-replysend-from-an-alias-but-it-is-rejected-whats-wrong)
- [Does AnonAddy strip out the banner information when I reply to an email?](#does-anonaddy-strip-out-the-banner-information-when-i-reply-to-an-email)
- [How do I send email from an alias?](#how-do-i-send-email-from-an-alias)
- [Will people see my real email if I reply to a forwarded one?](#will-people-see-my-real-email-if-i-reply-to-a-forwarded-one)
- [Can emails have attachments?](#can-emails-have-attachments)
- [What is the max email size limit?](#what-is-the-max-email-size-limit)
- [What happens if I have a subscription but then cancel it?](#what-happens-if-i-have-a-subscription-but-then-cancel-it)
- [If I subscribe will Stripe see my real email address?](#if-i-subscribe-will-stripe-see-my-real-email-address)
- [How do you prevent spammers?](#how-do-you-prevent-spammers)
- [What do you use to do DNS lookups on domain names?](#what-do-you-use-to-do-dns-lookups-on-domain-names)
- [Is there a limit to how many emails I can forward?](#is-there-a-limit-to-how-many-emails-i-can-forward)
- [Is there a limit to how many aliases I can create per hour?](#is-there-a-limit-to-how-many-aliases-i-can-create-per-hour)
- [How is my bandwidth calculated?](#how-is-my-bandwidth-calculated)
- [How many emails can I receive before I go over my bandwidth limit?](#how-many-emails-can-i-receive-before-i-go-over-my-bandwidth-limit)
- [What happens if I go over my bandwidth limit in a given month?](#what-happens-if-i-go-over-my-bandwidth-limit-in-a-given-month)
- [Can I login using an additional username?](#can-i-login-using-an-additional-username)
- [I'm not receiving any emails, what's wrong?](#im-not-receiving-any-emails-whats-wrong)
- [I'm having trouble logging in, what's wrong?](#im-having-trouble-logging-in-whats-wrong)
- [How do I know this site won't disappear next month?](#how-do-i-know-this-site-wont-disappear-next-month)
- [What happens to AnonAddy if you die?](#what-happens-to-anonaddy-if-you-die)
- [Is the application tested?](#is-the-appliction-tested)
- [How do I host this myself?](#how-do-i-host-this-myself)
- [Who's behind AnonAddy?](#whos-behind-anonaddy)
- [I couldn't find an answer to my question, how can I contact you?](#i-couldnt-find-an-answer-to-my-question-how-can-i-contact-you)

## Why is it called AnonAddy?

AnonAddy is short for "Anonymous Email Address". The word "Addy" is internet slang for email address, e.g.

> "My addy is being spammed. I should've kept it private."

## Why did you make this site?

I made this service after trying a few other options that do a similar thing. I was really interested in how they worked and loved the thought of protecting my real email addresses from spam.

I also wanted to address some issues with other services such as:

* Proprietary closed source code
* Adverts, analytics and trackers used on the sites
* No option to encrypt emails using a GPG/OpenPGP key
* No option for multiple recipients

I made the code open-source to show everyone what was going on behind the scenes and to allow others to help improve the application.

I use this service myself for the vast majority of sites I'm signed up to.

## Why should I use AnonAddy?

There are a number of reasons you should consider using this service:

* Protect your real email address from spam by simply deactivating/deleting aliases that receive unsolicited emails
* Identify who has sold your data by using a different email address for every site
* Protect your identity in the event of a data breach by making it difficult for hackers to cross-reference your accounts
* Prevent inbox snooping by encrypting all inbound emails using GPG/OpenPGP encryption
* Update where emails are forwarded without having to go through and change your email address for each site individually
* Reply to forwarded emails anonymously without revealing your true email address

## Do you store emails?

No I definitely do not store/save any emails that pass through the server.

## What is a shared domain alias?

A shared domain alias is any alias that has a domain name that is also shared with other users. For example anyone can generate an alias with the @anonaddy.me domain. Aliases with shared domain names must be pre-generated and cannot be created on-the-fly like standard aliases.

## What is a standard alias?

A standard alias is any alias that can be created on-the-fly. Automatic on-the-fly alias creation is only available for domains that are unique to you. For example, your unique username subdomain, any additional usernames or any custom domains. So if you signed up with the username "johndoe", any alias you create using @johndoe.anonaddy.com would be a standard alias (even if you've generated a Random Character/Random Word one).

## Can I use my own domain?

Yes you can use your own domain name so you can also have *@example.com as your aliases. To do so you simply need to add a TXT record to verify your ownership of the domain. Then you will need to add an MX record to your domain so that our server can handle incoming emails. You can then add a few other records to enable sending from your domain too.

## Can I add a domain and also use it as a recipient?

No, you cannot use the same domain as a custom domain and also for a recipient on AnonAddy.

e.g if you add "example.com" as a custom domain, you cannot then add "xyz@example.com" as a recipient. This is because a domain cannot direct email to multiple locations simultaneously using MX records. So your email would arrive for "example.com" and then attempt to be forwarded to "xyz@example.com" which would create a loop.

You can instead use a subdomain for your custom domain, e.g. "mail.example.com" instead of "example.com", this would allow you to create *@mail.example.com for your aliases. More details can be found [here](https://anonaddy.com/help/adding-a-custom-domain/).

## Can I add a domain if I'm already using it for email somewhere else?

If you have a custom domain say **example.com** and you are already using it for email somewhere else e.g. ProtonMail or Namecheap then you cannot also use it simultaneously with AnonAddy.

This is because emails cannot be handled by multiple different mail servers at the same time, even if they have the same priority MX records. It can only be delivered to one mail server at a time which will typically be the MX record with the smallest number since this has the highest priority.

You can either:

- Migrate your domain to AnonAddy by removing the current provider's MX records and adding AnonAddy's.
- Or, if you would like to keep using your domain with your current email provider then I would recommend instead adding a subdomain of it to AnonAddy such as **mail.example.com**.

Using a subdomain will not interfere with your current email setup and you'll be able to create aliases ***@mail.example.com** through AnonAddy.

## Why should I use this instead of a similar service?

Here are a few reasons I can think of:

* Bring your own GPG/OpenPGP key to encrypt your forwarded emails (and the option to replace subjects)
* No adverts
* No analytics or trackers (just server access logs)
* No third party content
* Open-source application code
* No limitation on the number of aliases that can be created
* Generous monthly bandwidth
* Multiple domains to choose for aliases (currently anonaddy.com, anonaddy.me and another 3 for paid plan users)
* Ability to generate random character and random word aliases at shared domains
* Ability to add additional usernames to compartmentalise aliases
* New features added regularly

## Is there a browser extension?

Yes there is an [open-source](https://github.com/anonaddy/browser-extension) browser extension available to download for [Firefox](https://addons.mozilla.org/en-GB/firefox/addon/anonaddy/) and [Chrome](https://chrome.google.com/webstore/detail/anonaddy/iadbdpnoknmbdeolbapdackdcogdmjpe) (also available on other chromium based browsers such as Brave and Vivaldi). You can use the extension to generate new aliases remotely.

## Is there an Android app?

Yes, there is an excellent [open-source](https://gitlab.com/Stjin/anonaddy-android) Android app created by [Stjin](https://twitter.com/Stjinchan) that is available to download from the [Play Store](https://play.google.com/store/apps/details?id=host.stjin.anonaddy) (paid) and [F-Droid](https://f-droid.org/packages/host.stjin.anonaddy) (free). The developer of this app has put in a lot of time and effort so if you would like to support him please purchase the Play Store version.

There is also another [open-source](https://github.com/KhalidWar/anonaddy) Android app created by [KhalidWar](https://github.com/KhalidWar) available on the [Play Store](https://play.google.com/store/apps/details?id=com.khalidwar.anonaddy).

## Is there an iOS app?

Yes, [KhalidWar's](https://github.com/KhalidWar) [open-source](https://github.com/KhalidWar/anonaddy) app from above is also available on the [App Store](https://apps.apple.com/us/app/addymanager/id1547461270).

## Is there a Raycast extension?

Yes, [http.james'](https://httpjames.space/) [open-source](https://github.com/raycast/extensions/tree/cceffa51046266f25819f800316561b783c52663/extensions/anonaddy/) extension is available on the [Raycast Store](https://www.raycast.com/http.james/anonaddy).

## How do I add my own GPG/OpenPGP key for encryption?

On the recipients page you simply need to click "Add public key" and paste in your **public** key data. Now all emails forwarded to you will be encrypted with your key. You can even hide and encrypt the subject as AnonAddy supports protected headers.

## Are attachments encrypted too?

Yes attachments are part of the email body and are also encrypted if you have it enabled.

## Are forwarded emails signed when encryption is enabled?

Yes when you have encryption enabled all forwarded emails are signed using our mailer@anonaddy.me private key.

You can add this key to your own keyring so that you can verify emails have come from us.

The fingerprint of the mailer@anonaddy.me key is "26A987650243B28802524E2F809FD0D502E2F695" you can find the key on [https://keys.openpgp.org](https://keys.openpgp.org/search?q=26A987650243B28802524E2F809FD0D502E2F695).

## What if I don't want anyone to link ownership of my aliases together?

If you're concerned that your aliases are all linked by your username e.g. @johndoe.anonaddy.com, then you have a couple of options:

1. You can generate random character or random word aliases instead, these are all under a shared domain and cannot be linked to a user.
2. You can add additional usernames and separate your aliases under each of them. e.g. you could have one username for personal stuff, another for work, another for hobbies etc.

## Where is the server located?

The server is located in Amsterdam, Netherlands with [Greenhost.net](https://greenhost.net/). Greenhost focuses greatly on privacy and security and their servers run entirely on Dutch wind energy. The backup mail server is located in Warsaw, Poland with [UpCloud](https://upcloud.com).

## What if I don't trust you?

It's good to keep your guard up when online so you should never trust anyone 100%. I'll try my best to be as honest and transparent as I can but if you still aren't convinced you can always just fire up your own server and self-host this application. You'll need to know about server administration and PHP. You can find more information here [https://github.com/anonaddy/anonaddy#self-hosting](https://github.com/anonaddy/anonaddy#self-hosting).

## What is the maximum number of recipients I can add to an alias?

The limit is currently set to 10 which should suffice in the vast majority of situations.

## What happens when I delete my account?

When you delete your account the following happens:

* All of your recipients are deleted from the database
* All of your aliases that use a shared domain e.g. @anonaddy.me are soft deleted from the database (this is to prevent any chance of another user generating the same alias in the future) any identifying information e.g the alias description is removed
* All of your other aliases are deleted from the database
* All of your custom domains are deleted from the database
* Your user details are deleted from the database
* Your username and any additional usernames that you created are encrypted and added to a table in the database. This is to prevent anybody signing up with the same username in the future.
* Any subscription information is deleted from the database

## Does this work with any email provider?

Yes this will work with any provider, although I can't guarantee it won't land in spam initially.

## How do I reply to a forwarded email?

Each forwarded email has a From: header set. This header will look something like this:

From: <<span class="break-words"><alias+hello=example.com@johndoe.anonaddy.com></span>>

Where hello@example.com is the address of the person who sent you the email and alias@johndoe.anonaddy.com is the alias that forwarded you the email.

All you need to do is click reply in your email client or web interface and it will automatically fill the To: field with the correct address.

To check if a reply has worked properly check in your dashboard if the reply count has been incremented for that alias.

For further details please see this help article - [Replying to email using an alias](https://anonaddy.com/help/replying-to-email-using-an-alias/).

## I'm trying to reply/send from an alias but the email keeps coming back to me, what's wrong?

If you are trying to reply or send from an alias but the email keeps coming back to yourself then it is most likely because you are not sending the message from an email address that **is not listed as a verified recipient** on your AnonAddy account.

If you try to reply or send from an alias using an unverified email address then the message will simply be forwarded to you as it would be if it was sent by any other sender.

Please double check that you are indeed sending from a verified recipient email address by inspecting your sent items to see which address it was actually sent from.

## I'm trying to reply/send from an alias but it is rejected, what's wrong?

If you see the rejection message `550 5.1.1 Recipient address rejected: Address does not exist` then this means that the alias has either been deleted or does not yet exist (and you do not have catch-all enabled), you must restore (or create) it before you can send/reply from it.

If you receive an email notification with the subject "Attempted reply/send from alias has failed" then it is usually because you have a verified recipient that is using your own domain which does not have a DMARC policy.

> Note: This is referring to **your verified recipient address** on your AnonAddy account **and not** any of your custom domains or the email address that you are replying / sending to

When replying or sending from an alias, **additional checks** are carried out to ensure it is not a spoofed email. Your AnonAddy recipient's email domain must pass DMARC checks in order to protect against spoofed emails and to make sure that the reply/send from attempt definitely came from your recipient.

For example if the verified recipient on your AnonAddy account is `hello@example.com` and you get this email notification then it is because the domain "example.com" does not have a DMARC policy in place.

To resolve this you simply need to add a DMARC record, for example:

| Type | Host   | Value                       |
|:-----|:-------|:----------------------------|
| TXT  | _dmarc | "v=DMARC1; p=quarantine; adkim=s" |


You should also have SPF and DKIM records in place.

To learn more about DMARC please see this site - [https://dmarc.org/](https://dmarc.org/).

If your AnonAddy recipient is with a popular mail service provider for example: Gmail, Outlook, Tutanota, Mailbox.org, Protonmail etc. then they will already have a DMARC policy in place so you do not need to take any action.

## Does AnonAddy strip out the banner information when I reply to an email?

Yes, the email banner "This email was sent to..." will be automatically removed when you reply to any messages. You can test this by replying to yourself from one of your aliases.

## How do I send email from an alias?

This works in the same way as replying to an email.

Let's say that you have the alias **first@johndoe.anonaddy.com** and you want to send an email to **hello@example.com**.

All you need to do is enter the following in the To: field.

<<span class="break-words"><first+hello=example.com@johndoe.anonaddy.com></span>>

> **Note**: you must send the email from a verified recipient on your account.

Then send the email exactly as you would any other. To check that the email has sent successfully, look in your dashboard at the sent count column and see if it has been incremented for that alias.

If you want an easy way to construct the correct email address that you should send to you can click "Send from" next to any alias in the web application and after entering the destination address it will display the right email address to use.

This works exactly the same for shared domain aliases, additional usernames and custom domains.

You can even use the send from feature to create an alias on the fly that does not yet exist. This only works for standard aliases or those at custom domains that behave as a catch-all.

You must generate aliases that use shared domains (e.g. circus.waltz449@anonaddy.me) beforehand in order to be able to send from them.

If you need to send an email to an address with an extension e.g. **hello+whatever@example.com** then it's exactly the same method:

<<span class="break-words"><first+hello+whatever=example.com@johndoe.anonaddy.com></span>>

Just enter the extension too!

For further details please see this help article - [Sending email from an alias](https://anonaddy.com/help/sending-email-from-an-alias/).

## Will people see my real email if I reply to a forwarded one?

No, your real email will not be shown, the email will look as if it has come from us instead. Just make sure not to include anything that might identify you when composing the reply, i.e. your full name.

## Can emails have attachments?

Yes you can add attachments to emails forwarded and replies. Attachments count towards your bandwidth.

## What is the max email size limit?

The max email size is currently set to 25MB (including attachments).

## What happens if I have a subscription but then cancel it?

If you cancel your subscription it will remain active until the end of your current billing cycle, you will still be able to use your paid plan features until the billing cycle ends.

A few days before your billing cycle ends you will receive an email letting you know the steps you need to take to prevent the loss of any emails. Shortly after ending the following will happen:

* Any custom domains will be **deactivated**
* Any additional usernames will be **deactivated**
* If you have any more than **2 recipients** they will be **deleted**
* Paid account settings will be reverted to default values
* Any aliases using paid plan only domains will be **deactivated**
* If you have any more than 20 aliases using a shared domain e.g. anonaddy.me they will be **deactivated**
* If your account username has catch-all disabled then it will be enabled

You will not be able to activate any of the above again until you resubscribe.

## If I subscribe will Stripe see my real email address?

When you subscribe you can choose which email to provide to Stripe, feel free to use an alias. This email will be used for notifications from Stripe such as; if your card payment fails or if your card has expired.

## How do you prevent spammers?

The following is in place to help prevent spam:

* Rspamd - Fast, free and open-source spam filtering system
* DNS blacklist checks - spamhaus.org
* SPF, DKIM - to check the SPF record on the sender's domain
* DMARC - to check for email spoofing and reject emails that fail
* FQDN - the sender must be using a valid fully qualified domain name
* PTR record check - if the sender has no valid PTR record it is rejected

## What do you use to do DNS lookups on domain names?

The server is running a local DNS caching server to improve the speed of queries.

## Is there a limit to how many emails I can forward?

Not unless you are really going to town. Each user is throttled to 200 emails per hour through the server.

## Is there a limit to how many aliases I can create per hour?

Currently you are limited to creating 10 new aliases per hour on the free plan, 20 per hour on the Lite plan and 50 per hour on the Pro plan. If you try to create more than this the emails will be deferred until you are back below the limit.

## How is my bandwidth calculated?

Each time a new email is received Postfix calculates its size in bytes. A column in the database is then simply incremented by that size when the email is forwarded or a reply is sent. At the start of each month your bandwidth is reset to 0.

I don't use rolling 30 day total as the only way to do this would be to log the date and size of every single email received.

Blocked emails do not count towards your bandwidth (e.g. if an alias is inactive or deleted).

## How many emails can I receive before I go over my bandwidth limit?

The average email is about 76800 bytes (75KB), this is roughly equivalent to 7,000 words in plain text. So the 10MB monthly allowance would be around 140 emails and the Lite plan's 100MB would be almost 1,400 emails.

## What happens if I go over my bandwidth limit in a given month?

If you get close to your limit (over 80%) you'll be sent an email letting you know. If you continue and go over your limit the server will respond to any delivery attempts to your aliases with the following: `552 5.2.2 Recipient address rejected: User over quota` until your bandwidth resets the next month or you upgrade your plan.

## Can I login using an additional username?

Yes, you can login with any of your usernames. You can add 1 additional username as a Lite user and up to 10 additional usernames as a Pro user for totals of 2 and 11 respectively (including the one you signed up with).

## I'm not receiving any emails, what's wrong?

Please make sure to add mailer@anonaddy.me, mailer@anonaddy.com and any other aliases you use to your address book and also to check your spam folder. Make sure to mark emails from AnonAddy as safe if they turn up in spam.

If an alias has been deleted and you try to send email to it, the emails will be rejected with an error message - "550 5.1.1 Recipient address rejected: Address does not exist".

Check that you have not deactivated the alias, custom domain or additional username. When any of these are deactivated, emails will be silently discarded, they will not be rejected or return any error message.

The sender of the email may be failing SPF, DMARC or DNS blacklist checks resulting in the email being rejected. The sender should also have correct reverse DNS setup and use a FQDN as their hostname.

If you are forwarding emails to an icloud.com email address some users are having issues with a small number of emails being rejected (often those from Facebook).

For some reason Apple seems to think these emails are spam/phishing and returns this error message:

> Diagnostic-Code: smtp; 550 5.7.1 [CS01] Message rejected due to local policy.

If you are having issues with emails being rejected as "possibly spammy" by Google, iCloud or Microsoft then please try the following steps if you can:

1. **Replace the email subject** by going to your settings in AnonAddy
2. Try adding a GPG key and **enabling encryption**. This will prevent the email's content being scanned and reduce the chance of it being rejected.
3. Enable the option to hide and encrypt the email subject
4. Try disabling the banner information on forwarded emails
5. Try adding the alias email (and/or domain) to your contact list (address book) or safe senders list if possible

For Outlook, Hotmail or MSN you can find instructions on how to add a domain to your safe senders list [here](https://support.microsoft.com/en-gb/office/safe-senders-in-outlook-com-470d4ee6-e3b6-402b-8cd9-a6f00eda7339).

I will also soon be adding an option to change the format of the display from part of the "From:" header.

If neither of the above options work then please try changing to another recipient so that you can continue to receive emails.

If you still aren't receiving emails please contact me.

## I'm having trouble logging in, what's wrong?

If you are having trouble logging in it will likely fall under one of the following scenarios:

1. Incorrect username

Please make sure you are using your account username (e.g. johndoe) and not your email address to try to login.

2. Forgotten password

If you've forgotten your password you can reset it by entering your username here - https://app.anonaddy.com/password/reset

3. Forgotten username

If you've forgotten your username you can request a reminder by entering your email address here - https://app.anonaddy.com/username/reminder

4. Lost 2FA device

Please use the backup code that you were shown when you enabled 2FA.

5. Errors with hardware security key

If you have a YubiKey and are using Windows and have an issue with your personal password/PIN you may need to reset the key using the YubiKey manager software.

## How do I know this site won't disappear next month?

I am very passionate about this project. I use it myself every day and will be keeping it running indefinitely. The service also provides me with an income.

## What happens to AnonAddy if you die?

I do have someone in place who can keep the service running in the event of me not being here. They are able to continue paying for the servers that host AnonAddy and the domains that it uses. All AnonAddy domains also always have over 5 years until they expire.

They would make a Twitter announcement informing all users that they would be keeping the service running. You would then be able to decide whether you'd like to continue using AnonAddy or start to update your email addresses.

## Is the application tested?

Yes it has over 200 automated PHPUnit tests written.

## How do I host this myself?

You will need to set up your own server with Postfix so that you can pipe the received mail to the application. You can find more information here [https://github.com/anonaddy/anonaddy#self-hosting](https://github.com/anonaddy/anonaddy#self-hosting).

For those who prefer using Docker there is an image you can use here - [github.com/anonaddy/docker](https://github.com/anonaddy/docker).

## Who's behind AnonAddy?

My name is Will Browning, I'm a web developer from the UK and an advocate for online privacy and open-source software. You can find me on [Twitter](https://twitter.com/willbrowningme) although I don't tweet that much!

## I couldn't find an answer to my question, how can I contact you?

For any other questions just send an email to - [contact@anonaddy.com](mailto:contact@anonaddy.com) ([GPG Key](https://anonaddy.com/anonaddy-contact-public-key.asc))

## Self Hosting

## Software Requirements

* Postfix (3.0.0+) (plus postfix-mysql for database queries and postfix-pcre)
* PHP (8.0+) and the [php-mailparse](https://pecl.php.net/package/mailparse) extension, the [php-gnupg](https://pecl.php.net/package/gnupg) extension if you plan to encrypt forwarded emails, the [php-imagick](https://pecl.php.net/package/imagick) extension for generating 2FA QR codes
* Port 25 unblocked and open
* Redis (6.x+) for throttling and queues
* FQDN as hostname e.g. mail.anonaddy.me
* MariaDB / MySQL
* Nginx
* Rspamd
* DNS records - MX, SPF, DKIM, DMARC
* Reverse DNS
* SSL/TLS Encryption - you can install a free certificate from Let’s Encrypt.

For full details please see the [self-hosting instructions file](SELF-HOSTING.md).

## My sponsors

Thanks to [Vlad Timofeev](https://github.com/vlad-timofeev), [Patrick Dobler](https://github.com/patrickdobler), [Luca Steeb](https://github.com/steebchen), [Laiteux](https://github.com/Laiteux), [narolinus](https://github.com/narolinus),[Limon Monte](https://github.com/limonte) and [Lukas](https://github.com/lunibo) for supporting me by sponsoring the project on GitHub!

Also an extra special thanks to [CrazyMax](https://github.com/crazy-max) for sponsoring me and also creating and maintaining the awesome [AnonAddy Docker image](https://github.com/anonaddy/docker)!

## Thanks

Huge thank you to [Stjin](https://twitter.com/Stjinchan) and [KhalidWar](https://github.com/KhalidWar) for their amazing mobile apps.

Also to [https://gitlab.com/mailcare/mailcare](https://gitlab.com/mailcare/mailcare) and [https://github.com/niftylettuce/forward-email](https://github.com/niftylettuce/forward-email) for their awesome open-source projects that helped me along the way.

## License

GNU Affero General Public License v3.0. Please see [License File](LICENSE.md) for more information.
