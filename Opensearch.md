# Opensearch


## Environment set-up

During environment set-up some things have to be done manually:

```bash
curl -X POST "https://${ES_HOST}/_aliases" \
-H 'Content-Type: application/json' \
-d '{
    "actions": [
        {
          "add": {
            "indices": "request-*",
            "alias": "request"
          }
        }
    ]
}'
```

```bash
curl -X POST "https://${ES_HOST}/_aliases" \
-H 'Content-Type: application/json' \
-d '{
    "actions": [
        {
            "add": {
                "indices": "datastore-request-*",
                "alias": "datastore-request"
            }
        }
    ]
}'
```

```bash
curl -X POST "https://${ES_HOST}/.kibana/_doc/index-pattern:datastore-request" \
-H 'Content-Type: application/json' \
-d '{
    "type": "index-pattern",
    "index-pattern": {
        "title": "datastore-request-*",
        "timeFieldName": "@timestamp"
    }
}'
```

