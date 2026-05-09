# Local WordPress Test Site

This starts a disposable WordPress site with Smart Search Chips mounted from this repo.

## Start

```powershell
npm run local:wp:start
```

Open:

- Site: http://localhost:8899
- Admin: http://localhost:8899/wp-admin

Login:

- Username: `admin`
- Password: `password`

## What It Seeds

- Activates the `trailforge` test theme.
- Activates the local `smart-search-chips` plugin from this repository.
- Creates a random outdoor gear catalog with `sschp_tags` meta on each post.
- Sets the homepage to a test landing page with `[sschp_search]`.

Try searches like:

- `tent`
- `stove`
- `waterproof`
- `ultralight`
- `coffee`
- `sleep`

## Useful Commands

```powershell
npm run local:wp:seed
npm run local:wp:logs
npm run local:wp:stop
npm run local:wp:reset
```

`local:wp:reset` deletes the local database and WordPress volume, then recreates the site.
