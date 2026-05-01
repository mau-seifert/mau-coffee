# Image Processing for Showcase

The `image-processing.sh` script automatically compresses and resizes images placed in `/var/www/mau.coffee/public/showcase/`.
The script is not in this repo. I suggest having a similar script if you use this website as a template for your own.

## How it works

1. **Input** any `.jpg`, `.jpeg`, or `.png` file dropped into the directory.
2. **Processing** for each new original the script:
   - Generates a UUID‑based filename.
   - Creates a high‑quality JPEG (85% quality) resized to max 1920px width.
   - Creates a thumbnail JPEG (80% quality) resized to max 400px width with `_thumbnail` appended.
   - Strips all metadata (Exif, comments, etc.).
   - Deletes the original file in the end.
3. **Skipping** originals that have already been processed are skipped (tracked in `.processed_images`).

## Dependencies

- **ImageMagick** (`convert`) for resizing and conversion.
- **uuidgen** for generating unique filenames (part of `uuid-runtime`).

Install with:
```bash
sudo apt install imagemagick uuid-runtime
```

## Running the script

From the server, as the deploy-user (or root):
```bash
bash /home/deploy-user/image-processing.sh
```

An idea would also be to set up a cron job to run it automatically every few minutes:
```bash
crontab -e -u deploy-user
*/5 * * * * /bin/bash /home/deploy-user/image-processor.sh
```

## Output filenames

- Main image: `<UUID>.jpg`
- Thumbnail: `<UUID>_thumbnail.jpg`

Example:  
`3e7c4b2e-9f1a-45d6-8a3c-1e9f0b2a6e7c.jpg`  
`3e7c4b2e-9f1a-45d6-8a3c-1e9f0b2a6e7c_thumbnail.jpg`

## My Customisation

- **Quality & sizes** edit the variables at the top of the script (e.g. `MAIN_QUALITY=85`, `MAIN_MAX_WIDTH=1920`).
- **Delete original safely** the original is only deleted after both output files exist. If conversion fails, the original is left untouched.
- **Reset processing history** delete `.processed_images` to re‑process all images currently in the folder.