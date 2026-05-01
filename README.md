# mau-coffee

A minimal PHP-based static-ish site for showcasing images and blog posts.

## Description

mau-coffee is a small project that serves a simple website built with plain PHP templates. It includes a blog that uses leaflet.pub posts as its content source, a showcase for images, and a sitemap generator. The repo is intentionally lightweight and designed to be run with Docker for quick local development.

## Getting started

Prerequisites:
- Docker & Docker Compose
- Node.js & npm for building CSS with Tailwind

Quick start:

1. Clone the repository:

```
git clone https://tangled.org/mau.coffee/mau-coffee
cd mau-coffee
```

2. Start the application with Docker Compose:

```
docker-compose up --build -d
```

3. Open your browser:

```
http://localhost:8080
```

4. Build or watch CSS locally:

```
npm install
npm run build:css    # build once
npm run watch:css    # development watch
```

## Documentation

Further documentation and additional notes are available under [.docs](https://tangled.org/mau.coffee/mau-coffee/tree/main/.docs)

## License

This project is released under the MIT License. See the full text in [LICENCE](LICENCE).
