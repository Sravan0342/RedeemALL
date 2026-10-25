# Stage 1: Secure Azure VM web server

> Part of the **RedeemALL** idea: one place to track every credit card offer so none expire unused. See the [main page](../README.md) for the concept. The HTML here is just the first demo screen; the focus of this stage is the secured Azure hosting behind it.

## Goal
Create a Linux VM in Azure inside its own virtual network, lock it down with a network security group, and host a PHP web page on it with Apache.

## What I built
| Resource | Setting |
|----------|---------|
| Resource group | `RedeemALL` |
| Region | Canada Central |
| Virtual network (VNet) | `RedeemALL_vnet`, address space `10.0.0.0/16` |
| Subnet | `WebSUB` `10.0.1.0/24` (private subnet: no default outbound access) |
| Network security group (NSG) | Attached to the VM's network interface (rules below) |
| Virtual machine | Ubuntu 24.04 LTS, Gen 2, x64 |
| Sign-in | SSH with username and password |
| Web server | Apache2 with PHP |

## NSG inbound rules
| Name | Port | Protocol | Source | Action |
|------|------|----------|--------|--------|
| SSH | 22 | TCP | My own public IP only | Allow |
| HTTP | 80 | TCP | Any | Allow |
| (default) | All other ports | Any | Any | Deny |

## Architecture
```
Internet
   │  HTTP :80 (anyone)      SSH :22 (my IP only)
   ▼
NSG (on the VM's network interface)
   │
RedeemALL_vnet 10.0.0.0/16
 └─ WebSUB 10.0.1.0/24
     └─ Ubuntu 24.04 VM ── Apache2 + PHP ── /var/www/html
```

## Steps
1. Created the resource group `RedeemALL` in Canada Central.
2. Created the VNet `RedeemALL_vnet` (`10.0.0.0/16`) with the subnet `WebSUB` (`10.0.1.0/24`).
3. Created the Ubuntu 24.04 VM in that subnet.
4. Created the NSG, attached it to the VM's network interface, and allowed SSH from my IP only plus HTTP from anywhere.
5. Signed in over SSH and installed Apache2 and PHP (see [docs/SETUP-STEPS.md](docs/SETUP-STEPS.md)).
6. Copied `index.php` and the `IMAGES` folder from my laptop to the VM with WinSCP.
7. Moved them into the web folder, set owner `www-data` and permissions 755 for folders and 644 for files.
8. Started the Apache service and opened the site at `http://<vm-public-ip>/`.

## Files
| Path | What it is |
|------|------------|
| [`app/index.php`](app/index.php) | The PHP offers page served by Apache on the VM |
| [`app/IMAGES/`](app/IMAGES/) | Logo and offer icons used by the page (PNG + SVG) |
| [`app/basic.html`](app/basic.html) | The first static HTML version of the page |
| [`app/templates/`](app/templates/) | HTML templates and `css/style.css` for the RedeemALL app screens (offers, cards, add offer, login) |
| [`docs/SETUP-STEPS.md`](docs/SETUP-STEPS.md) | Commands used to install Apache/PHP, copy files and set permissions |
| [`screenshots/`](screenshots/) | Screenshots of the running site |

![Offers page running on the VM](screenshots/offers-page.png)

## Problems I hit
- **Browser showed PHP code instead of the page.** Apache wasn't serving PHP yet. Installing `libapache2-mod-php` and starting the Apache service fixed it.

## What I learned
- How a VNet, subnet and NSG fit together to control traffic to a VM.
- Limiting SSH to my own IP keeps port 22 closed to the rest of the internet.
- Linux file names and folders are case-sensitive (`IMAGES` is not `images`).
- Web files must be readable by the `www-data` user for Apache to serve them.

## Next improvements
- Switch from password sign-in to SSH keys.
- Add HTTPS (port 443) with a free Let's Encrypt certificate.
