# Stage 2 setup steps

## 1. Network and database VM (Azure portal)
1. In `RedeemALL_vnet`, add subnet `DbSUB` `10.0.2.0/24`. Untick **Enable private subnet** so the VM can download packages.
2. Create VM `redeemall-db` (Ubuntu 24.04, Standard_B1s) in `DbSUB`, **Public IP: None**, with a new NSG.
3. NSG inbound rules:

| Priority | Name | Source | Port | Action |
|---|---|---|---|---|
| 100 | Allow-MySQL-from-web | 10.0.1.0/24 | 3306 | Allow |
| 110 | Allow-SSH-from-web | 10.0.1.0/24 | 22 | Allow |
| 4000 | Deny-other-VNet | VirtualNetwork | Any | Deny |

## 2. Install MySQL (from the web VM)
```bash
ssh <admin-user>@<db-vm-private-ip>     # jump from the web VM
sudo apt update && sudo apt install -y mysql-server
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf   # bind-address = 0.0.0.0
sudo systemctl restart mysql
```

## 3. Create the database
```bash
sudo mysql < redeemall.sql      # or paste db/redeemall.sql after: sudo mysql
```
Use your own password in place of `CHANGE_ME`.

## 4. Web VM: connect PHP to MySQL
```bash
sudo apt install -y php-mysql
sudo systemctl restart apache2
```
Copy `app/index.php` and `app/db.example.php` to `/var/www/html/` with WinSCP, rename `db.example.php` to `db.php`, and put in the DB VM's private IP and the password. Keep the `IMAGES` folder from Stage 1 in the same place.

## 5. Test
Open `http://<web-vm-public-ip>/`. The offers now come from MySQL, and **Mark as used** saves to the database.
