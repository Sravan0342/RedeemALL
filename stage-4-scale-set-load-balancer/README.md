# Stage 4: Load balancing with a versioned VM Scale Set

> Part of the **RedeemALL** idea: one place to track every credit card offer so none expire unused. See the [main page](../README.md).
> So far the site runs on **one** web VM: if it stops, RedeemALL is down. In Stage 4 the web tier becomes a **VM Scale Set** behind an **Azure Load Balancer**. Several identical web servers share the traffic, Azure adds more when it's busy, and new releases roll out as **image versions**.

## Goal
- Run the website on **2 or more identical VMs**, built from a saved, versioned image of my web server.
- Put an **Azure Load Balancer** in front, so visitors use one public IP and traffic is spread across healthy VMs.
- **Autoscale**: add VMs when CPU is high, remove them when it's quiet.
- Release updates by publishing a **new image version** and rolling it out across the scale set.

## Design
```
                    Visitors
                       │ HTTP :80
                       ▼
          Azure Load Balancer (Standard, public IP)
          health probe: HTTP GET / on port 80
             ┌─────────┼─────────┐
             ▼         ▼         ▼
          web-0     web-1     web-n      VM Scale Set (image: RedeemALL-web v1.0.0)
             └─────────┼─────────┘        WebSUB 10.0.1.0/24, autoscale 2 to 4
                       │
         ┌─────────────┴──────────────┐
         ▼ MySQL :3306                ▼ private endpoint
   DB VM (Stage 2)              Storage account (Stage 3)
   DbSUB 10.0.2.0/24            StorageSUB 10.0.3.0/24
```

| Resource | Setting | Why |
|----------|---------|-----|
| Azure Compute Gallery | Image definition `redeemall-web`, versions `1.0.0`, `1.0.1`, ... | Every release is a numbered, repeatable server image |
| VM Scale Set | Built from the latest image version, in `WebSUB`, 2 instances to start | Identical web servers instead of one |
| Load Balancer | Standard SKU, public IP, rule TCP 80 → 80 | One address for visitors; traffic spread across VMs |
| Health probe | HTTP `/` on port 80 | A VM that stops answering gets no traffic |
| Autoscale | Scale out when CPU > 70% for 5 min; scale in when CPU < 30%; min 2, max 4 | Pay for more servers only when needed |
| NSG | Allow 80 from Internet and the `AzureLoadBalancer` tag; SSH only from my IP | Same security as Stage 1 |
| Upgrade policy | Rolling, one VM at a time | New versions go out with no downtime |
| Shared data | MySQL (Stage 2) and Blob Storage (Stage 3) | Every VM shows the same offers and images |

## Steps
1. Prepare the Stage 3 web VM as the "golden" server: Apache, PHP, `index.php`, `db.php`, blobfuse2 mount.
2. Add the server name to the page footer (`gethostname()`), so a refresh shows which VM answered.
3. Create an **Azure Compute Gallery** and capture the web VM as image version **1.0.0**.
4. Create the **VM Scale Set** from that image version, in `WebSUB`, with 2 instances.
5. Create the **Standard Load Balancer** with a public IP, backend pool = the scale set, health probe and rule on port 80.
6. Add the **autoscale** rules.
7. Test: refresh the load balancer's IP and watch the server name change between VMs.
8. Test failover: stop one instance and confirm the site stays up.
9. Release **1.0.1** (for example a small page change): capture a new image version and roll it out.
10. Retire the single Stage 1 web VM once the scale set is serving traffic.

## Cost (rough)
Standard Load Balancer about **$18 a month**, plus about **$8 to $10 a month for each B1s VM** running. Scale down to 0 or delete the scale set when not demoing.

## Status
Planned.
