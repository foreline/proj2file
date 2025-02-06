<?php
declare(strict_types=1);

namespace Foreline\Proj2File;

use Foreline\IO\Message;
use Symfony\Component\Finder\Finder;
use Webmozart\Assert\Assert;

/**
 *
 */
class ProjectPacker
{
    private const OUTPUT_DIR = '.proj2file';
    
    /**
     * @param string $path
     * @return string
     */
    public function pack(string $path = ''): string
    {
        if ( empty($path) ) {
            $path = getcwd();
        }
        
        Message::info('Working directory: ' . $path);
        
        $this->ensureOutputDirectoryExists();
    
        $finder = $this->createFinder();
        
        $content = [];
        
        $content[] = $this->getFileStructure();
        
        foreach ( $finder as $file) {
            $relativePath = substr($file->getPathname(), strlen(getcwd()) + 1);
            $content[] = $this->formatFileEntry($relativePath, $file->getContents());
        }
        
        return $this->writeOutputFile($content);
    }
    
    /**
     * @param bool $includeDirectories
     * @return Finder
     */
    private function createFinder(bool $includeDirectories = false): Finder
    {
        Message::info('Current directory: "' . getcwd() . '"');
        
        $finder = Finder::create()
            ->in(getcwd())
            ->ignoreVCS(true)
            ->ignoreVCSIgnored(true)
            ->notName(['*.lock'])
        ;
        
        if ( !$includeDirectories ) {
            $finder->files();
        }
        
        return $finder;
    }
    
    /**
     * @param string $path
     * @return string
     */
    public function getFileStructure(string $path = ''): string
    {
        
        $output = 'Project Structure:' . PHP_EOL;
        $output .= '```' . PHP_EOL;
        //$output .= '=================' . PHP_EOL;
        
        $finder = $this->createFinder(true);
        
        foreach ( $finder as $file ) {
            
            $relativePath = substr($file->getPathname(), strlen(getcwd()) + 1);
            
            // Calculate indentation level
            $parts = explode(DIRECTORY_SEPARATOR, $relativePath);
            $level = count($parts) - 1;
            
            // Print directory structure with colors
            if ( 0 === $level ) {
                $output .= $parts[0] . PHP_EOL;
            } elseif ( 1 === $level ) {
                $output .= "├── {$parts[count($parts) - 1]}" . PHP_EOL;
            } else {
                $output .= str_repeat('│   ', $level - 1) . "└── {$parts[count($parts) - 1]}" . PHP_EOL;
            }
        }
        
        //$output .= '=================' . PHP_EOL;
        $output .= "Total files found: " . iterator_count($finder) . PHP_EOL;
        $output .= '```' . PHP_EOL;
    
        return $output;
    }
    
    /**
     * @param string $path
     * @param string $content
     * @return string
     */
    private function formatFileEntry(string $path, string $content): string
    {
        Assert::string($content);
        
        // Escape existing triple backticks
        $escapedContent = str_replace('```', '\`\`\`', $content);
        
        if ( array_key_exists('extension', $pathInfo = pathinfo($path)) ) {
            $extension = $pathInfo['extension'];
        } else {
            $extension = '';
        }
        
        return <<<EOT
{$path}
```{$extension}
{$escapedContent}
```

EOT;
    }
    
    /**
     * @return void
     */
    private function ensureOutputDirectoryExists(): void
    {
        $outputDir = getcwd() . '/' . self::OUTPUT_DIR;
        
        if ( !file_exists($outputDir) ) {
            mkdir($outputDir, 0755, true);
        }
    }
    
    /**
     * @param array $content
     * @return string
     */
    private function writeOutputFile(array $content): string
    {
        $outputDir = getcwd() . '/' . self::OUTPUT_DIR;
        
        $fileName = basename(getcwd()) . '_' . date('Y-m-d_H-i-s');
        
        $filePath = sprintf('%s/%s.md', $outputDir, $fileName);
        
        file_put_contents($filePath, implode("\n", $content));
        
        return $filePath;
    }
}