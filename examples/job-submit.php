<?php

use Detail\Blitline\Client\BlitlineClient;

$config = require 'bootstrap.php';

$imageUrl = isset($_GET['image_url']) ? $_GET['image_url'] : null;

if (!$imageUrl) {
    throw new RuntimeException('Missing or invalid parameter "image_url"');
}

$imageSize = isset($_GET['image_size']) ? $_GET['image_size'] : 200;
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
$jobBuilder->setDefaultOption(
    'function.save',
    array(
        's3_destination' => array(
            'bucket' => $getConfig('s3bucket'),
//            'key' => $getConfig('s3path') . '/' . $imageName . '-' . $imageSize . '_blitline.jpg',
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
                    's3_destination' => array(
//                        'bucket' => $getConfig('s3bucket'),
                        'key' => $getConfig('s3path') . '/' . $imageName . '-' . $imageSize . '_blitline.jpg',
                    ),
                )
            )
    );

if (isset($config['version'])) {
    $job->setVersion($config['version']);
}

$response = $blitline->submitJob($job);

var_dump($response->getResult());
