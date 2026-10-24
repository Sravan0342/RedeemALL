# RedeemALL PHP page: setup on Ubuntu

What is in this folder:

- `index.php` is the offers page (your basic.html, now in PHP).
- `IMAGES/` has the pictures: `logo.png`, `favicon.png`, `hotel-cashback.png`, `travel-card.png`, `flight-miles.png`, `dining.png`, `shopping.png`, `fuel.png`, `no-offers.png`. Each one also has an `.svg` copy, which stays sharp at any size.
- `preview.png` shows how the page should look.

## 1. Install Apache and PHP on the server

```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php
php -v                      # should print a PHP version
```

## 2. Copy the files from your PC to the server

Run this on your own PC, from the folder that holds `index.php` and `IMAGES`.
Replace `azureuser` and `YOUR_SERVER_IP` with your own values.

```bash
scp -r index.php IMAGES azureuser@YOUR_SERVER_IP:/tmp/
```

## 3. Move them into the web folder

Back on the server:

```bash
sudo mkdir -p /var/www/html/redeemall
sudo mv /tmp/index.php /tmp/IMAGES /var/www/html/redeemall/
```

## 4. Set owner and permissions

Folders need 755 and files need 644, owned by the web server user `www-data`.

```bash
sudo chown -R www-data:www-data /var/www/html/redeemall
sudo find /var/www/html/redeemall -type d -exec chmod 755 {} \;
sudo find /var/www/html/redeemall -type f -exec chmod 644 {} \;
ls -l /var/www/html/redeemall/IMAGES
```

## 5. Open port 80 and test

- Azure portal: open your VM, go to **Networking**, and add an inbound rule for port **80** (HTTP).
- On the server, if the firewall is on: `sudo ufw allow 80/tcp`
- Restart Apache: `sudo systemctl restart apache2`
- In your browser go to `http://YOUR_SERVER_IP/redeemall/`

## Things to check if pictures do not show

- Linux cares about upper and lower case. The folder must be named `IMAGES` exactly. If your folder is `images`, change line 7 of `index.php` to `$IMAGE_DIR = 'images';`.
- Look at the error log: `sudo tail -n 20 /var/log/apache2/error.log`
- If the browser downloads `index.php` instead of showing it, PHP is not active: `sudo a2enmod php*` and then restart Apache.

## Adding your own offer

Copy one line in the `$demoOffers` list near the top of `index.php`, give it a new `id`, and set `image` to a file name in `IMAGES/`. Open `http://YOUR_SERVER_IP/redeemall/?reset=1` to reload the list.
