import type { Vendor } from '@/types';

export default function VendorDocumentIssues({
    issues,
}: {
    issues: Vendor['document_issues'];
}) {
    if (issues.length === 0) {
        return null;
    }

    return (
        <ul className="space-y-0.5 text-sm">
            {issues.map((issue) => (
                <li
                    key={issue.message}
                    className={
                        issue.status === 'action_needed'
                            ? 'text-red-700 dark:text-red-400'
                            : 'text-amber-700 dark:text-amber-400'
                    }
                >
                    {issue.message}
                </li>
            ))}
        </ul>
    );
}
