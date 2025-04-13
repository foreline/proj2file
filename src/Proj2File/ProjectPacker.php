<?php
declare(strict_types=1);

namespace Foreline\Proj2File;

use Exception;
use Foreline\IO\Response;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * This class is responsible for packing a project directory into a markdown file.
 * It includes methods to set the path, include line numbers, and format the output.
 */
class ProjectPacker
{
    private const OUTPUT_DIR  = '.proj2file';
    private const CONFIG_DIR  = self::OUTPUT_DIR . '/config';
    private const CONFIG_FILE = self::CONFIG_DIR . '/config.yml';
    
    private string $path;
    private bool $includeLineNumbers = false;
    private string $numberFormat = '4d';
    
    private array $exclusions = [
        'files' => [],
        'directories' => []
    ];
    
    private int $projectSize = 0;
    private int $projectLines = 0;
    
    /**
     *
     */
    public function __construct(/*string $path = ''*/)
    {
        //$this->setPath($path);
        $this->loadExclusions();
    }
    
    private function loadExclusions(): void
    {
        $configDir = Path::canonicalize(getcwd() . '/' . self::CONFIG_DIR);
        
        if ( !is_dir($configDir) ) {
            mkdir($configDir, 0755, true);
            return;
        }
        
        $configFile = getcwd() . '/' . self::CONFIG_FILE;
        
        if ( !file_exists($configFile) ) {
            file_put_contents(
                $configFile,
                <<<YAML
# Exclusion patterns configuration
exclusions:
  files:
    - "*.log"
    - "*.tmp"
    - "*.bak"
  directories:
    - "node_modules"
    - "vendor"
    - ".git"
YAML
            );
            return;
        }
        
        try {
            $config = Yaml::parseFile($configFile);
            
            $this->exclusions = array_merge([
                'files' => [],
                'directories' => []
            ], $config['exclusions']);
        } catch (Exception $e) {
            Response::warn(sprintf(
                'Failed to load exclusions from %s: %s',
                $configFile,
                $e->getMessage()
            ));
        }
    }
    
    /**
     * Sets the path to the project directory.
     *
     * @param string $path The path to the project directory.
     * @return void
     * @throws InvalidArgumentException if the path is not a valid directory.
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
     * Gets the path to the project directory.
     *
     * @return string The path to the project directory.
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
        ;
        
        foreach ( $this->exclusions['directories'] as $dir ) {
            $finder->exclude([$dir]);
        }
    
        $filenamePatterns = array_merge(
            $this->exclusions['files'],
            ['*.lock', 'package-lock.json', '*.ico', '*.svg', '*.png', '*.jpg', '*.jpeg']
        );
    
        $finder->notName($filenamePatterns);
        
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
        
        $output .= PHP_EOL;
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
        
        // count line breaks in $content array of strings
        $this->projectLines = array_reduce($content, function ($carry, $item) {
            return $carry + substr_count($item, "\n");
        }, 0) + count($content);
        
        file_put_contents($filePath, implode("\n", $content));
        
        $this->projectSize = filesize($filePath);
        
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
    
    /**
     * Returns the number of lines in the packed file
     * @return int
     */
    public function getLinesCount(): int
    {
        return $this->projectLines;
    }
    
    /**
     * Returns final file size
     * @return int
     */
    public function getSize(): int
    {
        return $this->projectSize;
    }
    
}