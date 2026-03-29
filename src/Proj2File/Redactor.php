<?php
declare(strict_types=1);

namespace Foreline\Proj2File;

/**
 * Detects and masks sensitive data in file contents.
 */
class Redactor
{
    private const PLACEHOLDER = '***REDACTED***';
    
    private int $redactionCount = 0;
    
    /** @var array<string, string> pattern name => regex */
    private array $patterns = [];
    
    public function __construct()
    {
        $this->registerDefaultPatterns();
    }
    
    /**
     * Registers built-in patterns for common secret types.
     */
    private function registerDefaultPatterns(): void
    {
        // Private keys (PEM blocks)
        $this->patterns['private_key'] =
            '/-----BEGIN\s(?:RSA\s|EC\s|DSA\s|OPENSSH\s)?PRIVATE\sKEY-----[\s\S]*?-----END\s(?:RSA\s|EC\s|DSA\s|OPENSSH\s)?PRIVATE\sKEY-----/';
        
        // Generic env-style secrets: KEY=value in .env and config files
        $this->patterns['env_secret'] =
            '/(?<=^|[\r\n])(?:[\w]*(?:SECRET|PASSWORD|PASSWD|TOKEN|API_KEY|APIKEY|ACCESS_KEY|PRIVATE_KEY|AUTH|CREDENTIAL|DB_PASS)[\w]*)(\s*[=:]\s*)([^\s\r\n#].{0,200})/i';
        
        // AWS access key IDs
        $this->patterns['aws_access_key'] =
            '/\bAKIA[0-9A-Z]{16}\b/';
        
        // GitHub personal access tokens (classic and fine-grained)
        $this->patterns['github_token'] =
            '/\bgh[ps]_[A-Za-z0-9_]{36,255}\b/';
        
        // GitLab personal/project access tokens
        $this->patterns['gitlab_token'] =
            '/\bglpat-[A-Za-z0-9\-_]{20,}\b/';
        
        // OpenAI API keys
        $this->patterns['openai_key'] =
            '/\bsk-[A-Za-z0-9]{20,}\b/';
        
        // Generic Bearer tokens in code/config
        $this->patterns['bearer_token'] =
            '/Bearer\s+[A-Za-z0-9\-_\.]{20,}/i';
        
        // Slack tokens
        $this->patterns['slack_token'] =
            '/\bxox[baprs]-[A-Za-z0-9\-]{10,}\b/';
        
        // Basic auth in URLs: scheme://user:password@host
        $this->patterns['url_credentials'] =
            '#(?<=[/]{2})[^\s/:@]+:[^\s/:@]+(?=@)#';
        
        // Connection strings with password parameter (semicolon-delimited contexts like ADO/ODBC)
        $this->patterns['connection_string_password'] =
            '/(?<=;)\s*(?:password|pwd)\s*=\s*["\']?([^"\'\s;&]{1,200})/i';
        
        // Email addresses
        $this->patterns['email'] =
            '/\b[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Z|a-z]{2,}\b/';
        
        // IPv4 addresses (non-loopback, non-example)
        $this->patterns['ipv4'] =
            '/\b(?!127\.)(?!0\.0\.0\.0)(?!255\.255)(?:(?:25[0-5]|2[0-4]\d|[01]?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d?\d)\b/';
        
        // JSON Web Tokens
        $this->patterns['jwt'] =
            '/\beyJ[A-Za-z0-9\-_]+\.eyJ[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_.+\/=]+\b/';
        
        // PHP-style array credential assignments: $VAR['PASSWORD'] = 'value';
        $this->patterns['php_array_secret'] =
            '/(\$\w+\[\s*[\'"](?:PASSWORD|PASSWD|SECRET|TOKEN|API_KEY|APIKEY|ACCESS_KEY|PRIVATE_KEY|AUTH|CREDENTIAL|DB_PASS)[\'"]]\s*=\s*[\'"])([^\'"]{1,200})([\'"])/i';
        
        // Generic high-entropy hex secrets (32+ hex chars, likely a key/hash)
        $this->patterns['hex_secret'] =
            '/(?<=[=:"\'\s])[0-9a-f]{32,}\b/i';
    }
    
    /**
     * Adds a custom redaction pattern.
     *
     * @param string $name   Unique pattern name
     * @param string $regex  PCRE regex pattern
     */
    public function addPattern(string $name, string $regex): void
    {
        $this->patterns[$name] = $regex;
    }
    
    /**
     * Removes a built-in or custom pattern by name.
     *
     * @param string $name
     */
    public function removePattern(string $name): void
    {
        unset($this->patterns[$name]);
    }
    
    /**
     * Returns available pattern names.
     *
     * @return string[]
     */
    public function getPatternNames(): array
    {
        return array_keys($this->patterns);
    }
    
    /**
     * Redact sensitive data in the given content.
     *
     * @param string $content  Raw file content
     * @return string          Content with secrets replaced
     */
    public function redact(string $content): string
    {
        foreach ($this->patterns as $name => $pattern) {
            // env_secret and connection_string_password: keep the key name, redact only the value
            if ($name === 'env_secret' || $name === 'connection_string_password') {
                $content = preg_replace_callback($pattern, function (array $matches): string {
                    $this->redactionCount++;
                    return str_replace($matches[count($matches) - 1], self::PLACEHOLDER, $matches[0]);
                }, $content) ?? $content;
                continue;
            }
            
            // PHP array secret: keep $VAR['KEY'] = ' prefix and trailing quote, redact only the value
            if ($name === 'php_array_secret') {
                $content = preg_replace_callback($pattern, function (array $matches): string {
                    $this->redactionCount++;
                    return $matches[1] . self::PLACEHOLDER . $matches[3];
                }, $content) ?? $content;
                continue;
            }
            
            $result = preg_replace_callback($pattern, function (): string {
                $this->redactionCount++;
                return self::PLACEHOLDER;
            }, $content);
            
            if ($result !== null) {
                $content = $result;
            }
        }
        
        return $content;
    }
    
    /**
     * Returns the total number of redactions performed.
     */
    public function getRedactionCount(): int
    {
        return $this->redactionCount;
    }
    
    /**
     * Resets the redaction counter.
     */
    public function resetCount(): void
    {
        $this->redactionCount = 0;
    }
}
