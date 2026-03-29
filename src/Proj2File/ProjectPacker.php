<?php
declare(strict_types=1);

namespace Foreline\Proj2File;

use Exception;
use Foreline\IO\Response;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

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
    private ?Redactor $redactor;
    private int $tailLines = 0;
    private bool $stripComments = false;
    private bool $dedup = false;
    private int $maxLineLength = 500;
    
    /** @var string[] */
    private array $commands = [];
    
    /** @var string[] */
    private array $extraPaths = [];
    
    /** @var array<string, string[]>  */
    private array $exclusions = [
        'files' => [],
        'directories' => []
    ];
    
    private int $projectSize = 0;
    private int $projectLines = 0;
    private int $tokensCount = 0;
    
    /**
     *
     */
    public function __construct(/*string $path = ''*/)
    {
        //$this->setPath($path);
        $this->redactor = new Redactor();
        $this->loadExclusions();
    }
    
    /**
     * @return void
     */
    private function loadExclusions(): void
    {
        $configDir = Path::canonicalize(getcwd() . '/' . self::CONFIG_DIR);
        
        if ( !is_dir($configDir) ) {
            if ( !mkdir($configDir, 0755, true) ) {
                dump('Failed to create config directory: ' . $configDir);
                return;
            }
        }
        
        $configFile = getcwd() . '/' . self::CONFIG_FILE;
        
        if ( !file_exists($configFile) ) {
            $result = file_put_contents(
                $configFile,
                <<<YAML
# Exclusion patterns configuration
exclusions:
  files:
    - ".gitignore"
    - ".dockerignore"
    - "*.log"
    - "*.tmp"
    - "*.bak"
    - "*.swp"
    - "*.DS_Store"
    - "*.zip"
    - "*.tar.gz"
    - "*.tar"
  directories:
    - "node_modules"
    - "vendor"
    - ".git"
YAML
            );
            if ( !$result ) {
                dump('Failed to create config file: ' . $configFile);
            }
            return;
        }
        
        try {
            $config = Yaml::parseFile($configFile);
            
            $this->exclusions = array_merge([
                'files' => [],
                'directories' => []
            ], $config['exclusions']);
        } catch ( Exception $e ) {
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
     * @param bool $enabled
     */
    public function setRedact(bool $enabled): void
    {
        $this->redactor = $enabled ? new Redactor() : null;
    }
    
    /**
     * Returns the Redactor instance, if active.
     */
    public function getRedactor(): ?Redactor
    {
        return $this->redactor;
    }
    
    /**
     * @param int $lines Number of lines to keep from the end of each file (0 = no limit)
     */
    public function setTailLines(int $lines): void
    {
        $this->tailLines = $lines;
    }
    
    /**
     * @param string[] $commands Shell commands whose output will be captured
     */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }
    
    /**
     * @param string[] $paths Additional directory/file paths to include
     */
    public function setExtraPaths(array $paths): void
    {
        $this->extraPaths = $paths;
    }
    
    /**
     * @param bool $enabled Whether to strip comment lines and blank lines
     */
    public function setStripComments(bool $enabled): void
    {
        $this->stripComments = $enabled;
    }
    
    /**
     * @param bool $enabled Whether to deduplicate repeated lines and truncate long lines
     * @param int $maxLineLength Maximum line length before truncation (0 = no truncation)
     */
    public function setDedup(bool $enabled, int $maxLineLength = 500): void
    {
        $this->dedup = $enabled;
        $this->maxLineLength = $maxLineLength;
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
            $content[] = $this->formatFileEntry($relativePath, $this->readFileContent($file->getPathname()));
        }
        
        // Pack extra paths (files or directories from other locations)
        foreach ($this->extraPaths as $extraPath) {
            $resolved = Path::canonicalize($extraPath);
            
            if (is_file($resolved)) {
                $fileContent = $this->readFileContent($resolved);
                if ($fileContent !== '') {
                    $content[] = $this->formatFileEntry($resolved, $fileContent);
                }
            } elseif (is_dir($resolved)) {
                $extraFinder = Finder::create()
                    ->in($resolved)
                    ->files()
                    ->ignoreVCS(true)
                    ->ignoreDotFiles(false);
                
                foreach ($extraFinder as $file) {
                    $displayPath = $resolved . '/' . substr($file->getPathname(), strlen($resolved) + 1);
                    $content[] = $this->formatFileEntry($displayPath, $this->readFileContent($file->getPathname()));
                }
            } else {
                Response::warn("Extra path not found: $resolved");
            }
        }
        
        // Capture command output
        foreach ($this->commands as $command) {
            $content[] = $this->captureCommand($command);
        }
        
        // Remove empty entries (files with no content after processing)
        $content = array_filter($content, static fn(string $entry) => $entry !== '');
        
        return $this->writeOutputFile($content);
    }
    
    /**
     * Creates a Finder instance to search for files in the project directory.
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
            ->ignoreDotFiles(false)
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
     * Returns the file structure of the project as a string.
     * @return string
     */
    public function getFileStructure(): string
    {
        $output = 'Project Structure:' . PHP_EOL;
        $output .= '```' . PHP_EOL;
        
        $finder = $this->createFinder(true);
        $rootPath = $this->getPath();
        
        $filesCount = 0;
        
        // Build a proper tree structure
        /** @var array<string, mixed> $tree */
        $tree = [];
        
        foreach ( $finder as $file ) {
            $relativePath = substr($file->getPathname(), strlen($rootPath) + 1);
            $isDir = $file->isDir();
            $pathParts = explode(DIRECTORY_SEPARATOR, $relativePath);
            
            // Build the tree
            $current = &$tree;
            
            for ( $i = 0; $i < count($pathParts); $i++ ) {
                $part = $pathParts[$i];
                $isLastPart = ($i === count($pathParts) - 1);
                
                // If it's a file (last part and not a directory)
                if ( $isLastPart && !$isDir ) {
                    if (!isset($current['files'])) {
                        $current['files'] = [];
                    }
                    $current['files'][] = $part;
                } else { // It's a directory
                    if ( !isset($current['dirs']) ) {
                        $current['dirs'] = [];
                    }
                    if ( !isset($current['dirs'][$part]) ) {
                        $current['dirs'][$part] = [];
                    }
                    $current = &$current['dirs'][$part];
                }
            }
        }
        
        // Function to print the tree
        $printTree = function($tree, $prefix = '', $isRoot = true) use (&$printTree, &$output, &$filesCount) {
            // Process directories first
            if ( isset($tree['dirs']) ) {
                ksort($tree['dirs']);
                $dirs = array_keys($tree['dirs']);
                $numDirs = count($dirs);
                
                for ( $i = 0; $i < $numDirs; $i++ ) {
                    $dir = $dirs[$i];
                    $lastDir = ($i === $numDirs - 1);
                    $hasFiles = isset($tree['files']) && !empty($tree['files']);
                    $isLast = $lastDir && !$hasFiles;
                    //$isLastDir = $lastDir;
                    
                    if ( $isRoot ) {
                        $prefix = $isLast ? '+-- ' : '+-- ';
                        $output .= $prefix . $dir . PHP_EOL;
                        $connector = $isLast ? '    ' : '|   ';
                        $printTree($tree['dirs'][$dir], $connector, false);
                    } else {
                        $connector = $isLast ? '+-- ' : '+-- ';
                        $output .= $prefix . $connector . $dir . PHP_EOL;
                        
                        $newPrefix = $prefix . ($isLast ? '    ' : '|   ');
                        $printTree($tree['dirs'][$dir], $newPrefix, false);
                    }
                }
            }
            
            // Then process files
            if ( isset($tree['files']) ) {
                sort($tree['files']);
                $numFiles = count($tree['files']);
                
                $filesCount += $numFiles;
                
                for ( $i = 0; $i < $numFiles; $i++ ) {
                    $file = $tree['files'][$i];
                    $lastFile = ($i === $numFiles - 1);
                    $isLastFile = $lastFile;
                    
                    if ( $isRoot ) {
                        $prefix = $isLastFile ? '+-- ' : '+-- ';
                        $output .= $prefix . $file . PHP_EOL;
                    } else {
                        $connector = $lastFile ? '+-- ' : '+-- ';
                        $output .= $prefix . $connector . $file . PHP_EOL;
                    }
                }
            }
        };
        
        // Print the tree
        $printTree($tree);
        
        $output .= PHP_EOL;
        $output .= "Total files: " . $filesCount . PHP_EOL;
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
        // Redact sensitive data if enabled
        if ($this->redactor !== null) {
            $content = $this->redactor->redact($content);
        }
        
        // Strip comments and blank lines if enabled
        if ($this->stripComments) {
            $content = $this->removeComments($content, $path);
        }
        
        // Deduplicate repeated lines and truncate long lines if enabled
        if ($this->dedup) {
            $content = $this->deduplicateLines($content);
        }
        
        // Skip empty files (or files that became empty after processing)
        if (trim($content) === '') {
            return '';
        }
        
        // Escape existing triple backticks
        $escapedContent = str_replace('```', '\`\`\`', $content);
        
        if ( array_key_exists('extension', $pathInfo = pathinfo($path)) ) {
            $extension = $pathInfo['extension'];
        } else {
            $extension = $this->detectLanguageFromShebang($content);
        }
    
        // Split content into lines
        $lines = explode("\n", $escapedContent);
        
        // Apply tail truncation if configured
        $truncated = false;
        if ($this->tailLines > 0 && count($lines) > $this->tailLines) {
            $totalLines = count($lines);
            $lines = array_slice($lines, -$this->tailLines);
            $truncated = true;
            $skipped = $totalLines - $this->tailLines;
        }
    
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
        
        $truncationNotice = '';
        if ($truncated) {
            $truncationNotice = "... ($skipped lines truncated, showing last $this->tailLines lines)" . "\n";
        }
        
        return <<<EOT
$path
```$extension
$truncationNotice$contentBlock
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
     * @param string[] $content
     * @return string
     */
    private function writeOutputFile(array $content): string
    {
        $outputDir = getcwd() . '/' . self::OUTPUT_DIR;
        
        $fileName = basename(getcwd() ?: 'project') . '_' . date('Y-m-d_H-i-s');
        $latestFileName = basename(getcwd() ?: 'project') . '-latest';
        
        $filePath = sprintf('%s/%s.md', $outputDir, $fileName);
        $latestFilePath = sprintf('%s/%s.md', $outputDir, $latestFileName);
        
        // count line breaks in $content array of strings
        $this->projectLines = array_reduce($content, function ($carry, $item) {
            return $carry + substr_count($item, "\n");
        }, 0) + count($content);
        
        $this->tokensCount = TokenCounter::getCount(implode("\n", $content));
        
        file_put_contents($filePath, implode("\n", $content));
        file_put_contents($latestFilePath, implode("\n", $content));
        
        $this->projectSize = filesize($filePath) ?: 0;
        
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
    
    /**
     * @return int
     */
    public function getTokensCount(): int
    {
        return $this->tokensCount;
    }
    
    /**
     * Executes a shell command and formats its output as a markdown entry.
     *
     * @param string $command Shell command to execute
     * @return string Formatted markdown block
     */
    private function captureCommand(string $command): string
    {
        Response::info("Executing: $command");
        
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);
        
        $result = implode("\n", $output);
        
        // Redact if enabled
        if ($this->redactor !== null) {
            $result = $this->redactor->redact($result);
        }
        
        // Apply tail truncation
        if ($this->tailLines > 0) {
            $lines = explode("\n", $result);
            if (count($lines) > $this->tailLines) {
                $skipped = count($lines) - $this->tailLines;
                $lines = array_slice($lines, -$this->tailLines);
                $result = "... ($skipped lines truncated, showing last $this->tailLines lines)\n" . implode("\n", $lines);
            }
        }
        
        // Deduplicate repeated lines and truncate long lines if enabled
        if ($this->dedup) {
            $result = $this->deduplicateLines($result);
        }
        
        $exitInfo = $exitCode !== 0 ? " (exit code: $exitCode)" : '';
        
        return <<<EOT
Command: `$command`$exitInfo
```
$result
```

EOT;
    }
    
    /**
     * Detects the programming language from a shebang line.
     *
     * @param string $content File content
     * @return string Language identifier or empty string
     */
    private function detectLanguageFromShebang(string $content): string
    {
        $firstLine = strtok($content, "\n");
        
        if ($firstLine === false || !str_starts_with($firstLine, '#!')) {
            return '';
        }
        
        $shebangMap = [
            'php'    => 'php',
            'python' => 'python',
            'python3'=> 'python',
            'ruby'   => 'ruby',
            'node'   => 'javascript',
            'nodejs'  => 'javascript',
            'deno'   => 'typescript',
            'perl'   => 'perl',
            'bash'   => 'bash',
            'zsh'    => 'zsh',
            'fish'   => 'fish',
            'sh'     => 'sh',
            'lua'    => 'lua',
            'Rscript'=> 'r',
            'pwsh'   => 'powershell',
        ];
        
        foreach ($shebangMap as $keyword => $language) {
            if (str_contains($firstLine, $keyword)) {
                return $language;
            }
        }
        
        return '';
    }
    
    /**
     * Reads file content, transparently decompressing gzip files.
     *
     * @param string $filePath Absolute path to the file
     * @return string File content (empty string on failure)
     */
    private function readFileContent(string $filePath): string
    {
        if (str_ends_with($filePath, '.gz')) {
            $compressed = file_get_contents($filePath);
            if ($compressed === false) {
                Response::warn("Failed to read: $filePath");
                return '';
            }
            $decompressed = @gzdecode($compressed);
            if ($decompressed === false) {
                Response::warn("Failed to decompress: $filePath");
                return '';
            }
            return $decompressed;
        }
        
        $content = file_get_contents($filePath);
        return $content !== false ? $content : '';
    }
    
    /**
     * Removes comment lines and blank lines from content based on file type.
     *
     * @param string $content File content
     * @param string $path    File path (used to detect comment style)
     * @return string Content with comments and blank lines removed
     */
    private function removeComments(string $content, string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // Map file extensions to comment prefixes
        $commentPrefixes = match (true) {
            in_array($ext, ['conf', 'cfg', 'ini', 'sh', 'bash', 'zsh', 'yml', 'yaml', 'py', 'rb', 'pl', 'r', 'toml', 'env', '']) => ['#'],
            in_array($ext, ['php', 'js', 'ts', 'c', 'cpp', 'h', 'java', 'go', 'rs', 'swift', 'kt', 'cs', 'css', 'scss', 'less']) => ['//', '/*', '*/', '*'],
            in_array($ext, ['sql', 'lua']) => ['--'],
            in_array($ext, ['bat', 'cmd']) => ['REM', '::'],
            in_array($ext, ['xml', 'html', 'svg']) => ['<!--'],
            in_array($ext, ['vim']) => ['"'],
            default => ['#', '//'],
        };
        
        $lines = explode("\n", $content);
        $filtered = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Skip blank lines
            if ($trimmed === '') {
                continue;
            }
            
            // Skip comment lines
            $isComment = false;
            foreach ($commentPrefixes as $prefix) {
                if (str_starts_with($trimmed, $prefix)) {
                    $isComment = true;
                    break;
                }
            }
            
            if (!$isComment) {
                $filtered[] = $line;
            }
        }
        
        return implode("\n", $filtered);
    }
    
    /**
     * Deduplicates consecutive repeated lines and truncates long lines.
     *
     * @param string $content Raw content
     * @return string Deduplicated and truncated content
     */
    private function deduplicateLines(string $content): string
    {
        $lines = explode("\n", $content);
        $result = [];
        $prevNormalized = null;
        $repeatCount = 0;
        
        foreach ($lines as $line) {
            // Truncate long lines
            if ($this->maxLineLength > 0 && mb_strlen($line) > $this->maxLineLength) {
                $originalLength = mb_strlen($line);
                $line = mb_substr($line, 0, $this->maxLineLength) . "... ($originalLength chars, truncated)";
            }
            
            $normalized = $this->normalizeLine($line);
            
            if ($normalized === $prevNormalized) {
                $repeatCount++;
                continue;
            }
            
            // Flush previous repeated line
            if ($repeatCount > 0) {
                $result[] = "... (repeated $repeatCount more " . ($repeatCount === 1 ? 'time' : 'times') . ')';
            }
            
            $result[] = $line;
            $prevNormalized = $normalized;
            $repeatCount = 0;
        }
        
        // Flush final repeated line
        if ($repeatCount > 0) {
            $result[] = "... (repeated $repeatCount more " . ($repeatCount === 1 ? 'time' : 'times') . ')';
        }
        
        return implode("\n", $result);
    }
    
    /**
     * Normalize a line for dedup comparison by replacing timestamps and PIDs with placeholders.
     */
    private function normalizeLine(string $line): string
    {
        // ISO-style timestamps: 2026-03-26 03:11:50.465, 2026-03-26T03:11:50
        $n = preg_replace('/\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:[.,]\d+)?/', '<TS>', $line);
        
        // Syslog-style timestamps: Mar 26 03:11:50
        $n = preg_replace('/[A-Z][a-z]{2}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2}/', '<TS>', (string)$n);
        
        // Bracketed PIDs/TIDs: [1447], [12345]
        $n = preg_replace('/\[\d+\]/', '[*]', (string)$n);
        
        return (string)$n;
    }
    
}