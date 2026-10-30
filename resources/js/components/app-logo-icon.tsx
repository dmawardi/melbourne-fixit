import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path d="M12 2.5 1.5 11.5h3V21.5h6v-6.5h3v6.5h6V11.5h3L12 2.5Z" />
        </svg>
    );
}
