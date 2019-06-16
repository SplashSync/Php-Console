<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Splash\Console\Helper;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ZipArchive;
use Splash\Core\SplashCore as Splash;

/**
 * Splash Module Zip Builder
 */
class ZipBuilder {
    
    /**
     * Build Module Archive 
     *
     * @param string $targetFile
     * @param array $sources
     * 
     * @return bool
     */
    public static function build(string $targetFile, array $sources): bool
    {
        //====================================================================//
        // Ensure Module Final Build Directory Exists
        if (!self::ensureDirectoryExists($targetFile)) {
            return false;
        }

        //====================================================================//
        // Verify Zip Extention is Loaded
        if (!extension_loaded("zip")) {
            return Splash::log()->errTrace(
                'PHP : Zip PHP Extension is required to build Splash PHP Module.'
            );
        }

        //====================================================================//
        // Create the archive
        $zip = new ZipArchive();
        if (true !== $zip->open($targetFile, ZIPARCHIVE::CREATE)) {
            return Splash::log()->errTrace("Unable to Create Zip Archive: " . $targetFile);
        }
        
        //====================================================================//
        // Add the files
        $filesCount = 0;
        foreach ($sources as $index => $srcDirectory) {
            $innerDirectory = is_string($index) ? $index : "";
            $filesCount += self::addDir($zip, $srcDirectory, $innerDirectory);
        }

        if ($filesCount <= 0) {
            return Splash::log()->errTrace("No Files found for Zip Archive.");
        }
        
        //====================================================================//
        // Close the zip -- done!
        $zip->close();

        return true;
    }
    
    /**
     * Ensure Target Directory Exist or Create it 
     *
     * @param string $targetFile
     * 
     * @return bool
     */
    private static function ensureDirectoryExists(string $targetFile): bool
    {
        $filesystem = new Filesystem();

        //====================================================================//
        // Ensure Module Final Build Directory Exists
        if (!$filesystem->exists(dirname($targetFile))) {
            try {
                $filesystem->mkdir(dirname($targetFile));
            } catch (IOExceptionInterface $exception) {
                return Splash::log()->errTrace(
                    "An error occurred while creating your directory at ".$exception->getPath()
                );
            }
        }

        //====================================================================//
        // Verify Module Final Build Directory Exists
        if (!$filesystem->exists(dirname($targetFile))) {
            return Splash::log()->errTrace(
                "Unable to Create Final Module Build Directory: " . dirname($targetFile)
            );
        }

        //====================================================================//
        // Remove Last Build File if Exists
        if (!$filesystem->exists($targetFile)) {
            $filesystem->remove($targetFile);
        }
        
        return true;
    }
    
    /**
     * Add Full Directory to Zip Archive 
     *
     * @param string $targetFile
     * @param array $sources
     * 
     * @return int
     */
    private static function addDir(ZipArchive $zip, string $srcDirectory, string $innerDirectory = ""): int
    {
        //====================================================================//
        // List Files to Add on Zip
        $finder = new Finder();
        $finder->files()->in($srcDirectory);
        // check if there are any search results
        if (!$finder->hasResults()) {
            return 0;
        }

        //====================================================================//
        // Add the files
        foreach ($finder as $file) {
            $zip->addFile(
                (string) $file->getRealPath(),
                $innerDirectory.$file->getRelativePathname()
            );
        }

        return $finder->count();
    }    
}
