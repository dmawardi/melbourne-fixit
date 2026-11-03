export default function AppLogo() {
    return (
        <>
            <img
                src="/images/logo.webp"
                alt=""
                width={256}
                height={256}
                className="aspect-square size-8 shrink-0 rounded-md"
            />
            <div className="ml-1.5 grid flex-1 text-left">
                <span className="truncate font-brand text-xl leading-none font-extrabold">
                    <span className="text-navy dark:text-foreground">My </span>
                    <span className="text-cta">Reno</span>
                    <span className="text-navy dark:text-foreground">
                        {' '}
                        Mate
                    </span>
                </span>
            </div>
        </>
    );
}
