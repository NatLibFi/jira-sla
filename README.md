# jira-sla

Outputs SLA data on issue reaction/triage times.

Calculates data based on maximum reaction times based on issue priority.

## Setup

### Install dependencies

php composer.phar install

### Configure

Jira settings in settings.ini -> [sla].

The appropriate maximum reaction time for each priority level is set in settings.ini -> [sla].

## Usage

php jira-sla.php [startdate] [enddate]

 * startdate & enddate: YYYY-MM-DD
