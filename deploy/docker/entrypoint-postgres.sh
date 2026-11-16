#!/bin/sh
set -e
if [ ! -s /var/lib/postgresql/data/PG_VERSION ]; then
    su-exec postgres initdb -D /var/lib/postgresql/data
    echo "host all all 0.0.0.0/0 trust" >> /var/lib/postgresql/data/pg_hba.conf
    echo "listen_addresses='*'" >> /var/lib/postgresql/data/postgresql.conf
    su-exec postgres pg_ctl -D /var/lib/postgresql/data -w start
    su-exec postgres psql -c "ALTER USER postgres WITH PASSWORD 'postgres';"
    su-exec postgres createdb -O postgres gamepanel || true
    su-exec postgres pg_ctl -D /var/lib/postgresql/data -m fast -w stop
fi
exec su-exec postgres postgres -D /var/lib/postgresql/data
