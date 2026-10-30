[![add-on registry](https://img.shields.io/badge/DDEV-Add--on_Registry-blue)](https://addons.ddev.com)
[![tests](https://github.com/egg2-code-labs/ddev-wp-devkit/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/egg2-code-labs/ddev-wp-devkit/actions/workflows/tests.yml?query=branch%3Amain)
[![last commit](https://img.shields.io/github/last-commit/egg2-code-labs/ddev-wp-devkit)](https://github.com/egg2-code-labs/ddev-wp-devkit/commits)
[![release](https://img.shields.io/github/v/release/egg2-code-labs/ddev-wp-devkit)](https://github.com/egg2-code-labs/ddev-wp-devkit/releases/latest)

# DDEV Wp Devkit

## Overview

This add-on integrates Wp Devkit into your [DDEV](https://ddev.com/) project.

## Installation

```bash
ddev add-on get egg2-code-labs/ddev-wp-devkit
ddev restart
```

After installation, make sure to commit the `.ddev` directory to version control.

## Usage

| Command | Description |
| ------- | ----------- |
| `ddev describe` | View service status and used ports for Wp Devkit |
| `ddev logs -s wp-devkit` | Check Wp Devkit logs |

## Advanced Customization

To change the Docker image:

```bash
ddev dotenv set .ddev/.env.wp-devkit --wp-devkit-docker-image="ddev/ddev-utilities:latest"
ddev add-on get egg2-code-labs/ddev-wp-devkit
ddev restart
```

Make sure to commit the `.ddev/.env.wp-devkit` file to version control.

All customization options (use with caution):

| Variable | Flag | Default |
| -------- | ---- | ------- |
| `WP_DEVKIT_DOCKER_IMAGE` | `--wp-devkit-docker-image` | `ddev/ddev-utilities:latest` |

## Credits

**Contributed and maintained by [@egg2-code-labs](https://github.com/egg2-code-labs)**
