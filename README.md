# Cryptli
A Symfony CLI utility for the Defuse encryption library

### Installation
```sh
composer require wespals/cryptli
```

### View commands
```sh
php vendor/bin/cryptli list
```

### Examples:
#### Generates a new random key and returns the ASCII safe key string
```console
user@host:~$ php vendor/bin/cryptli cryptli:create-key
def0000072424335658f3a1e80c61857a7ccb868853e3077ed706d52b928ce6b39b2aba82ec3ff6dded87580e3d78016bd556617314cd1c957c02038ac27d8730afd2a5f
```

##### Encrypts a plaintext string using a secret key and returns the ciphertext
```console
user@host:~$ php vendor/bin/cryptli cryptli:encrypt 'mySecretP@$$w0rd' <key>
def50200b5686661d66993842bfb68fd450d02e4ef1f4a5fdfea387058c072fa31cc2f5cc6b6485c74c8f0a4e64741dcfcb55b73a10c1a5e3e61964b206c2cc7c650bf54f0649fee98d97519b3c28f9d644a7f763474e3d40a0787e0a96f0889242018f4
```

#### Decrypts a ciphertext string using a secret key
```console
user@host:~$ php vendor/bin/cryptli cryptli:decrypt <ciphertext> <key>
mySecretP@$$w0rd
```