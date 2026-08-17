#!/usr/bin/env bash
# PostToolUse hook (Write|Edit|MultiEdit): runs Larastan/PHPStan on the
# edited file and blocks (exit 2) with the findings if it reports errors.

INPUT=$(cat)

FILE=$(php -r '
$data = json_decode(file_get_contents("php://stdin"), true);
echo $data["tool_input"]["file_path"] ?? $data["tool_response"]["filePath"] ?? "";
' <<< "$INPUT" 2>/dev/null)

case "$FILE" in
    *.php) ;;
    *) exit 0 ;;
esac

[[ -f "$FILE" ]] || exit 0

cd /var/www/html || exit 0

OUTPUT=$(vendor/bin/phpstan analyse --no-progress --error-format=raw "$FILE" 2>&1)
STATUS=$?

if [[ $STATUS -ne 0 ]]; then
    echo "PHPStan found issues in $FILE:" >&2
    echo "$OUTPUT" >&2
    exit 2
fi

exit 0
