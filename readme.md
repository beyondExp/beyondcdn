# **BeyondCDN PHP Flysystem CDN Adapter**

This is a PHP Flysystem adapter for CDN (Content Delivery Network) integration. It is inspired by the Frosh BunnyCDN adapter and allows you to easily store and retrieve files from your CDN using Flysystem.

Installation
You can install the adapter using composer. Run the following command in your project directory:

javascript
Copy code
composer require beyondEXP/beyondcdn
Usage
To use this adapter, you need to create a Flysystem instance and pass the adapter to it. Here's an example:

```
use YourCompany\YourCdnAdapter\YourCdnAdapter;
use League\Flysystem\Filesystem;

$adapter = new YourCdnAdapter('your-cdn-username', 'your-cdn-api-key', 'your-cdn-zone-id');
$filesystem = new Filesystem($adapter);
Now you can use the Flysystem API to interact with your CDN. Here are some examples:
```

```// Write a file to the CDN
$filesystem->write('path/to/file.txt', 'contents');

// Check if a file exists on the CDN
$filesystem->has('path/to/file.txt');

// Read the contents of a file from the CDN
$contents = $filesystem->read('path/to/file.txt');

// Delete a file from the CDN
$filesystem->delete('path/to/file.txt');
```
Configuration
When creating a new adapter, you need to pass three parameters:

```
username: Your CDN username
apiKey: Your CDN API key
zoneId: The ID of the CDN zone you want to use
```

You can also pass an optional fourth parameter domain if you want to specify the CDN domain to use (e.g. if you're using a custom domain).

Contributing
If you find a bug or have a feature request, please create an issue on the Github repository for this project. Pull requests are also welcome.

License
This project is licensed under the MIT License. See the LICENSE file for details.