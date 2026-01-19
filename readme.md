# Bilinfo

A PHP project.

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer install
```

## Usage

```bash
php index.php
```

running locally: 
http://localhost:8080/

ideas: 
Upload file with car infoexample "TN14780JTEGG32M500015704  20045275008200420090127100000000SVART               2017010320181031201306281206"

If this raw already exist should be skipped.
otherwise upload to db.


data example 
Field Type Length Start posion

Identet String 7 1
Chassinummer String 19 8
Modellår Number 4 27
Typgodkännande nr. Number 11 31
Första registrering Number 8 42
Priva:mporterad Number 1 50
Avregistrerad datum Number 8 51
Färg String 20 59
Senast besiktning Number 8 79
Nästa besiktning Number 8 87
Senast registrering Number 8 95
Månadsregistrering (mmdd) Number 4 103



DONE 0. validation for text file 
1. validation for rows (dates, WIN)
2. validation for structure (win exist, dates..) If not exist ask user do you want to save this row?
DONE 3. unic WIN and identity


better: 
- when sort do not jump up
- show history of changes. When upload, what changes... 
