# RedeemALL Stage 2: login page connected to MySQL

Files:
- `users.sql`: adds the `users` table and the demo login (demo / demo123)
- `config.php`: database settings (goes OUTSIDE the web folder)
- `index.php`: login page + offers from MySQL (Mark used, Delete, Add offer)

## 1. On the DB VM: add the users table

Copy the contents of `users.sql`, then:

```bash
sudo mysql
```
Paste it and type `EXIT;`.

## 2. On the DB VM: let MySQL accept connections from the web VM

By default MySQL only listens to itself (127.0.0.1).

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```
Change `bind-address = 127.0.0.1` to `bind-address = 0.0.0.0`, save (Ctrl+O, Enter, Ctrl+X), then:

```bash
sudo systemctl restart mysql
sudo ss -ltnp | grep 3306      # should show 0.0.0.0:3306
```
This is safe because the NSG only lets WebSUB (10.0.1.0/24) reach port 3306.

## 3. On the web VM: install the PHP MySQL driver and test the connection

```bash
sudo apt install -y php-mysql mysql-client
sudo systemctl restart apache2
mysql -h 10.0.2.4 -u redeemuser -p REDEEMALL -e "SELECT COUNT(*) FROM offers;"
```
Use your DB VM's real private IP if it isn't 10.0.2.4. If this prints a number, the two VMs can talk.

## 4. On the web VM: put the files in place

Copy `config.php` and `index.php` to the web VM with WinSCP (to your home folder), then:

```bash
sudo mv ~/config.php /var/www/config.php
sudo nano /var/www/config.php        # set the DB IP and your real password
sudo chown root:www-data /var/www/config.php
sudo chmod 640 /var/www/config.php   # only Apache can read it

sudo cp /var/www/html/index.php /var/www/html/index-stage1.php   # backup of the old page
sudo mv ~/index.php /var/www/html/index.php
sudo chown www-data:www-data /var/www/html/index.php
sudo chmod 644 /var/www/html/index.php
```
Keep the `IMAGES` folder where it already is (`/var/www/html/IMAGES`).

## 5. Test

Open `http://<web-vm-public-ip>/` and sign in with **demo / demo123**.

## If something goes wrong
- **"Cannot connect to the database"**: check the IP and password in `/var/www/config.php`, that step 3's test works, and `sudo tail -n 20 /var/log/apache2/error.log`.
- **Blank page**: same error log.
- **"Wrong username or password"**: check step 1 ran: `SELECT username FROM users;` on the DB VM.
