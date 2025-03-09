<?php
declare(strict_types=1);

namespace Foreline\Proj2File;

use Foreline\IO\Response;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Webmozart\Assert\Assert;

/**
 *
 */
class ProjectPacker
{
    private const OUTPUT_DIR = '.proj2file';
    private string $path;
    private bool $includeLineNumbers = false;
    private string $numberFormat = '4d';
    
    /*public function __construct(string $path = '')
    {
        $this->setPath($path);
    }*/
    
    /**
     * @param string $path
     * @return void
     */
    public function setPath(string $path): void
    {
        $path = Path::normalize($path);
        
        if (
            str_starts_with($path, './')
            || str_starts_with($path, '../')
        ) {
            $path = getcwd() . '/' . $path;
        }
    
        $absolutePath = Path::canonicalize($path);
        
        if ( !is_dir($absolutePath) ) {
            throw new InvalidArgumentException("The path '$absolutePath' is not a valid directory");
        }
        $this->path = $absolutePath;
    }
    
    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * @return bool
     */
    public function isIncludeLineNumbers(): bool
    {
        return $this->includeLineNumbers;
    }
    
    /**
     * @param bool $includeLineNumbers
     */
    public function setIncludeLineNumbers(bool $includeLineNumbers): void
    {
        $this->includeLineNumbers = $includeLineNumbers;
    }
    
    /**
     * @return string
     */
    public function getNumberFormat(): string
    {
        return $this->numberFormat;
    }
    
    /**
     * @param string $numberFormat
     */
    public function setNumberFormat(string $numberFormat): void
    {
        $this->numberFormat = $numberFormat;
    }
    
    /**
     * @return string
     */
    public function pack(): string
    {
        Response::info('Working directory: ' . $this->getPath());
        
        $this->ensureOutputDirectoryExists();
    
        $finder = $this->createFinder();
        
        $content = [];
        
        $content[] = $this->getFileStructure();
        
        foreach ( $finder as $file) {
            $relativePath = substr($file->getPathname(), strlen($this->getPath()) + 1);
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
        Response::info('Current directory: "' . $this->getPath() . '"');
        
        $finder = Finder::create()
            ->in($this->getPath())
            ->ignoreVCS(true)
            ->ignoreVCSIgnored(true)
            ->notName([
                '*.lock', 'package-lock.json',
                '*.ico', '*.svg', '*.png', '*.jpg', '*.jpeg'
            ])
        ;
        
        if ( !$includeDirectories ) {
            $finder->files();
        }
        
        return $finder;
    }
    
    /**
     * @return string
     */
    public function getFileStructure(): string
    {
        $output = 'Project Structure:' . PHP_EOL;
        $output .= '```' . PHP_EOL;
        //$output .= '=================' . PHP_EOL;
        
        $finder = $this->createFinder(true);
        
        foreach ( $finder as $file ) {
            
            $relativePath = substr($file->getPathname(), strlen($this->getPath()) + 1);
            
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
    
        // Split content into lines
        $lines = explode("\n", $escapedContent);
    
        // Format with line numbers if requested
        if ( $this->isIncludeLineNumbers() ) {
            $formattedLines = [];
        
            foreach ($lines as $lineNumber => $line) {
                //$formattedLines[] = $this->formatLineNumber($lineNumber + 1) . '. ' . $line;
                $formattedLines[] = $this->formatLineNumber($lineNumber + 1) . ' | ' . $line;
            }
        
            $contentBlock = implode("\n", $formattedLines);
        } else {
            $contentBlock = implode("\n", $lines);
        }
        
        return <<<EOT
{$path}
```{$extension}
{$contentBlock}
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
    
    /**
     * @param int $number
     * @return string
     */
    private function formatLineNumber(int $number): string
    {
        $format = $this->getNumberFormat();
        
        if ( str_contains($format, ':') ) {
            [$align, $width] = explode(':', $format);
            $width = (int)$width;
    
            return match ( $align ) {
                'left' => str_pad((string) $number, $width, ' ', STR_PAD_RIGHT),
                'center' => str_pad((string) $number, $width, ' ', STR_PAD_BOTH),
                default => str_pad((string) $number, $width, ' ', STR_PAD_LEFT),
            };
        }
        
        return sprintf('%' . $format, $number);
    }
}