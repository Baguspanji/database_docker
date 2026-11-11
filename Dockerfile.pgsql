FROM pgvector/pgvector:pg17

RUN apt-get -o Acquire::Check-Valid-Until=false -o Acquire::Max-FutureTime=172800 update \
    && apt-get install -y --no-install-recommends \
        postgresql-17-postgis-3 \
    && rm -rf /var/lib/apt/lists/*
