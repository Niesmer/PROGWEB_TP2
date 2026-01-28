# Requirements

This application uses [**tc-lib-pdf**](https://github.com/tecnickcom/tc-lib-pdf), which requires the PHP GD extension.

**Setup:**

1. Open your `php.ini` file
2. Uncomment the following line:
   ```ini
   extension=gd
   ```
3. Configure the `.env` file by renaming `.env.example` to `.env` and fill missing infos
