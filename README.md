<h1 align="center">RedeemALL</h1>

<h3 align="center">Every credit card reward, in one place, used before it expires.</h3>

---

> **This repository is about an idea, not about HTML.**
> The pages and code you see here are a working sketch of the **RedeemALL** concept.
> I'm building it step by step with AI-assisted "vibe coding" and hosting it myself on Azure,
> to show the idea and my cloud skills, not to show off hand-written front-end code.

## The idea

Most people carry several credit cards, and each one comes with coupons, cashback offers, hotel and airline perks, dining discounts and reward points. They live in different bank apps, emails and statements, and many of them **quietly expire unused**. That's real money left on the table.

**RedeemALL** puts all of those offers in one simple list:

- **See everything in one place:** every offer from every card, side by side.
- **Know what's about to expire:** offers ending soon are flagged, so you use them in time.
- **Track what you've used:** mark an offer as used and see what's still available.
- **Stay safe:** only the last 4 digits of a card are ever stored, never the full number.

The goal is simple: **redeem all of it, waste none of it.**

## The build, stage by stage

Each stage adds one real-world cloud skill on top of the last, moving RedeemALL from a single demo page to a resilient, scalable app.

| Stage | What I build | Cloud skill it shows | Status |
|-------|--------------|----------------------|--------|
| 1 | [**Secure web server**](stage-1-azure-vm/): an Ubuntu VM in its own VNet, locked down with an NSG, serving the RedeemALL page with Apache and PHP | Networking, NSGs, Linux web hosting | Done |
| 2 | [**Private database**](stage-2-database-vm/): offers stored in MySQL on a second VM with **no public IP**, reachable only from the web server | Multi-tier design, private subnets, MySQL | Done |
| 3 | [**Storage account**](stage-3-storage-account/): offer images moved to Azure Blob Storage behind a private endpoint, read with a managed identity | Blob Storage, private endpoints, managed identity | Planned |
| 4 | [**Scale set and load balancer**](stage-4-scale-set-load-balancer/): the web server becomes a versioned VM Scale Set behind an Azure Load Balancer with autoscale | High availability, autoscaling, image versioning | Planned |

### Where it ends up (after Stage 4)
```
                         Visitors
                            │ HTTP
                            ▼
              Azure Load Balancer (public IP)            Stage 4
                 ┌──────────┼──────────┐
                 ▼          ▼          ▼
               web-0      web-1      web-n               Stage 4: VM Scale Set
                 └──────────┼──────────┘                 (Stage 1 web server, now versioned)
                            │            RedeemALL_vnet 10.0.0.0/16
              ┌─────────────┴──────────────┐
              ▼                            ▼
     MySQL DB VM, no public IP      Storage account, private endpoint
     Stage 2                        Stage 3
```

![RedeemALL live: offers loaded from MySQL](stage-2-database-vm/screenshots/live-offers-page.png)

*Live from my Azure web VM (Stage 2): every offer is read from MySQL on a private database VM. Demo data, no real cards.*

## How I'm building it

- **Idea and direction:** mine. I decide what RedeemALL should do and how it should feel.
- **Code:** written with AI help ("vibe coding"), then reviewed, deployed and tested by me.
- **Cloud:** I set up and secure the Azure infrastructure myself: VNet, NSGs, VMs, MySQL, Storage, Load Balancer and Scale Sets.

## Where it's heading

- Add offers by uploading a statement or screenshot instead of typing them in.
- Reminders before an offer expires.
- Later: pull offers automatically from bank websites, and a phone app.
