# Examples

## Security incident response

Suspicious activity detected on a server — collect configuration, authentication logs, and system state for analysis:

```shell
proj2file run /etc/ssh \
  --include /etc/pam.d \
  --include /etc/passwd \
  --include /etc/group \
  --include /etc/sudoers \
  --include /etc/sudoers.d \
  --include /var/log/auth.log \
  --include /var/log/secure \
  --include /var/log/faillog \
  --exec "last -50" \
  --exec "lastb -50" \
  --exec "who" \
  --exec "w" \
  --exec "ss -tlnp" \
  --exec "iptables -L -n" \
  --exec "find /etc -newer /etc/passwd -ls" \
  --exec "getent passwd" \
  --exec "crontab -l" \
  --exec "ls -la /tmp" \
  --tail 500 \
  --dedup
```

## Service troubleshooting

### Nginx + PHP-FPM

Web application returning errors — gather configs, logs, and service state:

```shell
proj2file run /etc/nginx \
  --include /etc/php/8.2/fpm \
  --include /var/log/nginx \
  --include /var/log/php8.2-fpm.log \
  --exec "systemctl status nginx" \
  --exec "systemctl status php8.2-fpm" \
  --exec "nginx -T" \
  --exec "journalctl -u nginx --since '24 hours ago' --no-pager" \
  --exec "journalctl -u php8.2-fpm --since '24 hours ago' --no-pager" \
  --exec "df -h" \
  --exec "free -h" \
  --exec "ss -tlnp" \
  --tail 300 \
  --strip-comments
```

### PostgreSQL

Database connectivity or performance issues:

```shell
proj2file run /etc/postgresql \
  --include /var/log/postgresql \
  --exec "systemctl status postgresql" \
  --exec "pg_lsclusters" \
  --exec "pg_isready" \
  --exec "journalctl -u postgresql --since '24 hours ago' --no-pager" \
  --exec "df -h" \
  --exec "free -h" \
  --tail 500 \
  --dedup \
  --strip-comments
```

### MySQL / MariaDB

```shell
proj2file run /etc/mysql \
  --include /var/log/mysql \
  --exec "systemctl status mysql" \
  --exec "journalctl -u mysql --since '24 hours ago' --no-pager" \
  --exec "df -h" \
  --exec "free -h" \
  --tail 500 \
  --dedup \
  --strip-comments
```

## Docker / containers

Container failures or networking issues — use `--tail` and `--dedup` to keep output manageable:

```shell
proj2file run /etc/docker \
  --include /opt/myapp/docker-compose.yml \
  --exec "docker ps -a" \
  --exec "docker stats --no-stream" \
  --exec "docker network ls" \
  --exec "docker logs myapp-web --tail 300" \
  --exec "docker logs myapp-db --tail 300" \
  --exec "docker inspect myapp-web" \
  --exec "journalctl -u docker --since '24 hours ago' --no-pager" \
  --exec "df -h" \
  --exec "free -h" \
  --tail 300 \
  --dedup
```
