# Recipes

A Symfony web app for creating, sharing and discovering recipes — with AI-estimated nutrition info, comments, saves and notifications.

## Features

- Create, edit and delete recipes (title, notes, servings, cook time, photo, ingredients, steps, tags)
- Public/private recipes with a discover feed and search
- Save other users recipes, comment on recipes, and get notified on saves/comments
- Asynchronous AI nutrition estimation per recipe (via Messenger + Claude), with `PENDING` / `DONE` / `FAILED` status
- JSON API for recipes, ingredients and tags used for ajax requests

## Stack

- PHP 8.4, Symfony 8.1
- Doctrine ORM, PostgreSQL 16
- Symfony Messenger for background ai nutrition estimation
- Nginx + PHP-FPM, Docker Compose
