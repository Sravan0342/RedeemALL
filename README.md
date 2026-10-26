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

## What you'll find here

| Stage | What it shows | Status |
|-------|---------------|--------|
| 1 | [**Azure VM web server**](stage-1-azure-vm/): a secured Ubuntu VM in its own network, serving the first RedeemALL offers page | Done |
| 2 | [**Private database VM**](stage-2/): offers saved in MySQL on a database VM with no public IP, reachable only from the web VM | Done |

Each stage builds on the previous one, moving from a single demo page toward a full app.

![RedeemALL live: offers loaded from MySQL](stage-2/screenshots/live-offers-page.png)

*Live from my Azure web VM (Stage 2): every offer is read from MySQL on a private database VM. Demo data, no real cards.*

## How I'm building it

- **Idea and direction:** mine. I decide what RedeemALL should do and how it should feel.
- **Code:** written with AI help ("vibe coding"), then reviewed, deployed and tested by me.
- **Cloud:** I set up and secure the Azure infrastructure myself (VNet, NSG, VM, Apache, PHP).

## Where it's heading

- Add offers by uploading a statement or screenshot instead of typing them in.
- Reminders before an offer expires.
- Later: pull offers automatically from bank websites, and a phone app.
