// After a failed submit, focus the first invalid field so keyboard and
// screen-reader users land where the problem is. Fields without a matching
// id keep their inline error but are skipped.
export function focusFirstError(
    errors: Record<string, string>,
    ids: Record<string, string>,
): void {
    const field = Object.keys(ids).find((key) => errors[key]);

    if (!field) {
        return;
    }

    document.getElementById(ids[field])?.focus();
}
