# Wichteln

Eine simple PHP Seite, mit der man Wichtel zuordnen kann


## Installation

 1. [Docker](https://docs.docker.com/) installieren
 2. Repo klonen: `git clone https://github.com/linusemrch2618/wichteln && cd wichteln`
 3. Docker starten: `docker compose up`

Dann läuft es auf [localhost:8080/index.php](http://localhost:8080/index.php)


## Setup

Wenn die Datei `wichtel_data.json` in `src/` nicht vorhanden ist, gelangt man automatisch zur Setup-Seite.\
Ein Reset kann durchgeführt werden, indem `?reset=1` an die URL angefügt wird.
