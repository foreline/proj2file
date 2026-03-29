# Installation

## Requirements

* PHP 8.1+
* Composer

## Global install (via Composer)

If the package is published on Packagist, install globally with:

```shell
composer global require foreline/proj2file
```

Then add Composer's bin directory to your `PATH`. Edit `~/.bashrc` or `~/.zshrc`:

```bash
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

Reload your shell:

```bash
source ~/.bashrc  # or ~/.zshrc
```

Verify installation:

```shell
proj2file --help
```

## Linux system-wide installation (from source)

To make `proj2file` accessible globally without Composer, clone and symlink:

```shell
# Clone the repository
git clone https://github.com/foreline/proj2file.git /opt/proj2file

# Create a symlink in /usr/local/bin
sudo ln -s /opt/proj2file/bin/proj2file /usr/local/bin/proj2file

# Make the script executable
chmod +x /opt/proj2file/bin/proj2file
```

Install dependencies:

```shell
cd /opt/proj2file
composer install --no-dev
```

Verify installation:

```shell
proj2file --help
```

Now `proj2file` is available globally from any directory.

## Install from source (local project)

```shell
git clone https://github.com/foreline/proj2file.git
cd proj2file
composer install
```

Run the CLI:

```shell
php bin/proj2file run
```

## Install as a dev dependency in another project

```shell
composer require-dev foreline/proj2file
```

Run as local dependency:

* macOS/Linux:

```shell
./vendor/bin/proj2file run
```

* Windows:

```shell
.\vendor\bin\proj2file.bat run
```
