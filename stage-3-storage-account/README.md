# Stage 3: Offer images in a private Azure Storage account

> Part of the **RedeemALL** idea: one place to track every credit card offer so none expire unused. See the [main page](../README.md).
> Until now the offer pictures sat in an `IMAGES` folder on the web VM's disk. In Stage 3 they move to **Azure Blob Storage**, so they're stored once, safely, and every web server can share them. That matters in [Stage 4](../stage-4-scale-set-load-balancer/), where there are several web servers.

## Goal
- Store offer images (and later, uploaded statements and screenshots) in a **Storage account**, not on a VM disk.
- Keep the storage account **private**: no public internet access, reachable only from inside `RedeemALL_vnet`.
- Let the web VM read the images **without any password or key in the code**, using a managed identity.

## Design
```
Visitor ── HTTP :80 ──► Web VM (Apache + PHP)                RedeemALL_vnet 10.0.0.0/16
                         WebSUB 10.0.1.0/24
                           │  /var/www/html/IMAGES  (mounted with blobfuse2)
                           │  signs in with its managed identity
                           ▼  HTTPS via private endpoint
                Storage account  ── container: offer-images
                StorageSUB 10.0.3.0/24 (private endpoint)
                public network access: DISABLED
```

| Resource | Setting | Why |
|----------|---------|-----|
| Storage account | Standard, LRS, Canada Central, in `RedeemALL` | Cheap, durable storage for files |
| Container | `offer-images`, private (no anonymous access) | Holds the offer pictures |
| Subnet | `StorageSUB` `10.0.3.0/24` | Home for the private endpoint |
| Private endpoint | Blob endpoint in `StorageSUB` + private DNS zone `privatelink.blob.core.windows.net` | The storage account gets a private 10.0.3.x address inside the VNet |
| Public access | Disabled | Nobody on the internet can reach the files directly |
| Web VM identity | System-assigned managed identity with the **Storage Blob Data Reader** role | Azure handles sign-in; no keys in code or config files |
| Mount | `blobfuse2` mounts the container at `/var/www/html/IMAGES` | The PHP page keeps working unchanged |

## Steps
1. Add subnet `StorageSUB` (`10.0.3.0/24`) to `RedeemALL_vnet`.
2. Create the storage account; under **Networking**, set public access to **Disabled**.
3. Create the private endpoint (target: blob) in `StorageSUB`, linked to the private DNS zone.
4. Create the `offer-images` container and upload the pictures from the Stage 1 `IMAGES` folder.
5. On the web VM, turn on the **system-assigned managed identity** and give it **Storage Blob Data Reader** on the storage account.
6. Check DNS from the web VM: `nslookup <account>.blob.core.windows.net` should return a **10.0.3.x** address.
7. Install `blobfuse2` on the web VM and mount `offer-images` at `/var/www/html/IMAGES`.
8. Refresh the site: the same pictures now come from Blob Storage.
9. Prove it's private: opening the blob URL from my laptop is refused.

## Cost (rough)
A few MB of images and a private endpoint cost about **$8 a month**, almost all of it for the private endpoint.

## Status
Planned.
