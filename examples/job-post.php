<?php

use Detail\Blitline\Client\BlitlineClient;

$config = require 'bootstrap.php';

$imageUrl = isset($_GET['imageUrl']) ? $_GET['imageUrl'] : null;

if (!$imageUrl) {
    throw new RuntimeException('Missing or invalid parameter "imageUrl"');
}

$imageSize = isset($_GET['imageSize']) ? $_GET['imageSize'] : 200;
$image = new SplFileInfo($imageUrl);
$imageName = $image->getBasename();

$getConfig = function($optionName) use ($config) {
    if (!isset($config[$optionName])) {
        throw new RuntimeException(sprintf('Missing configuration option "%s"', $optionName));
    }

    return $config[$optionName];
};

$blitline = BlitlineClient::factory($config);

/** @var \Detail\Blitline\Job\JobBuilder $jobBuilder */
$jobBuilder = $blitline->getJobBuilder();
$jobBuilder->addDefaultOption(
    'functions.save',
    array(
        's3_destination' => array(
            'bucket' => $getConfig('s3bucket'),
            'key' => $getConfig('s3path') . '/' . $imageName . '-' . $imageSize . '_blitline.jpg',
        ),
    )
);

$job = $jobBuilder->createJob()
    ->setSourceUrl($imageUrl)
    ->addFunction(
        $jobBuilder->createFunction()
            ->setName('resize_to_fit')
            ->setParams(
                array(
                    'width' => $imageSize,
                    'height' => $imageSize,
                    'only_shrink_larger' => true, // Don't upscale image
                )
            )
            ->setSaveOptions(
                array(
                    'image_identifier' => $imageName,
//                    's3_destination' => array(
//                        'bucket' => $getConfig('s3bucket'),
//                        'key' => $getConfig('s3path') . '/' . $imageName . '-' . $imageSize . '_blitline.jpg',
//                    ),
                ),
                true // Merge with defaults
            )
    );

if (isset($config['version'])) {
    $job->setVersion($config['version']);
}

var_dump($job->toArray()['functions'][0]);
exit;

$job = array(
    'src' => $imageUrl,
    'v' => isset($config['version']) ? $config['version'] : '1.21',
    'functions' => array(
        array(
            'name' => 'resize_to_fit',
            'params' => array(
                'width' => $imageSize,
                'height' => $imageSize,
                'only_shrink_larger' => true, // Don't upscale image
            ),
            'save' => array(
                'image_identifier' => $imageName,
                's3_destination' => array(
                    'bucket' => $getConfig('s3bucket'),
                    'key' => $getConfig('s3path') . '/' . $imageName . '-' . $imageSize . '_blitline.jpg',
                ),
            ),
        ),
    ),
);

$response = $blitline->postJob($job);

var_dump($response);
