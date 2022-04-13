# Install Solr Docker
``` docker pull solr ```

Run Docker
```
docker run -d -p 8983:8983 --name my_solr solr:8 
```

Install Core
```
docker exec -it my_solr solr create_core -c amp
```

You can now visit ```http://localhost:8983/solr/#/amp/core-overview```

See more - https://github.com/docker-solr/docker-solr/blob/master/README.md 

# Ingest 

Start Server
```php -S localhost:8000```

run ```php ingest.php```
