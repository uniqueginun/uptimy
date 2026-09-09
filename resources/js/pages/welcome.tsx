import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    BarChart3,
    Bell,
    Clock,
    Globe,
    ShieldCheck,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';

const steps = [
    {
        title: 'Add your site',
        description:
            "Paste a URL and we're watching it — no agents or config files to install.",
    },
    {
        title: 'Choose a check interval',
        description:
            'Every minute for something critical, or once an hour for the rest. You decide per endpoint.',
    },
    {
        title: 'See status the moment it changes',
        description:
            'A live status, the HTTP code behind it, and how long it has stayed that way.',
    },
];

const features = [
    {
        icon: Clock,
        title: 'Flexible check intervals',
        description:
            'Run checks as often as every minute, independently for each endpoint on a site.',
    },
    {
        icon: Activity,
        title: 'Real HTTP status tracking',
        description:
            'Every check records the response code it actually got — not just reachable or not.',
    },
    {
        icon: BarChart3,
        title: 'Uptime, at a glance',
        description:
            'See uptime over the last 24 hours next to the all-time average, so a fresh outage never hides behind old history.',
    },
    {
        icon: ShieldCheck,
        title: 'Certificates are verified',
        description:
            "If a site's TLS certificate is broken, we treat it as down — because that's what it is for your visitors.",
    },
    {
        icon: Globe,
        title: 'Multiple endpoints per site',
        description:
            'Watch your homepage, login, and API health check separately, on their own schedules.',
    },
    {
        icon: Bell,
        title: 'A full check history',
        description:
            'Every check is logged, so you can see exactly when something changed and for how long.',
    },
];

export default function Welcome() {
    const { auth, name } = usePage().props;

    return (
        <>
            <Head title="Website & endpoint uptime monitoring" />
            <div className="bg-background text-foreground min-h-screen">
                <header className="border-border/60 border-b">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-2">
                            <div className="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-5 fill-current" />
                            </div>
                            <span className="text-lg font-semibold">
                                {name}
                            </span>
                        </div>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>
                                            Get started
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <section className="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                        <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                            <div>
                                <Badge variant="secondary">
                                    Website &amp; API monitoring
                                </Badge>
                                <h1 className="mt-4 text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                                    Know the moment your site goes down.
                                </h1>
                                <p className="text-muted-foreground mt-4 text-lg text-pretty">
                                    {name} checks your sites on the schedule you
                                    choose, records what actually came back, and
                                    shows you uptime over the last 24 hours and
                                    all time — so you find out before your users
                                    do.
                                </p>
                                <div className="mt-8 flex flex-wrap items-center gap-3">
                                    <Button size="lg" asChild>
                                        <Link
                                            href={
                                                auth.user
                                                    ? dashboard()
                                                    : register()
                                            }
                                        >
                                            {auth.user
                                                ? 'Go to dashboard'
                                                : 'Start monitoring — it’s free'}
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                    {!auth.user && (
                                        <Button
                                            size="lg"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link href={login()}>Sign in</Link>
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <Card className="shadow-lg">
                                <CardContent className="grid gap-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="font-medium">
                                                yoursite.com
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                /
                                            </p>
                                        </div>
                                        <Badge className="gap-1.5 border-transparent bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                                            <span className="size-1.5 rounded-full bg-emerald-500" />
                                            Up
                                        </Badge>
                                    </div>

                                    <div className="grid grid-cols-3 gap-4 border-t pt-4">
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Checked
                                            </p>
                                            <p className="text-sm font-medium">
                                                every minute
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Uptime (24h)
                                            </p>
                                            <p className="text-sm font-medium">
                                                100%
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Uptime (all time)
                                            </p>
                                            <p className="text-sm font-medium">
                                                99.94%
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between border-t pt-4">
                                        <div>
                                            <p className="font-medium">
                                                yoursite.com
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                /api/health
                                            </p>
                                        </div>
                                        <Badge className="gap-1.5 border-transparent bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400">
                                            <span className="size-1.5 rounded-full bg-red-500" />
                                            Down · 503
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <section className="border-border/60 border-t">
                        <div className="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                            <div className="max-w-2xl">
                                <h2 className="text-3xl font-semibold tracking-tight">
                                    Set up in three steps
                                </h2>
                                <p className="text-muted-foreground mt-2">
                                    No dashboard tour required.
                                </p>
                            </div>

                            <ol className="mt-10 grid gap-8 sm:grid-cols-3">
                                {steps.map((step, index) => (
                                    <li key={step.title}>
                                        <span className="text-muted-foreground text-sm font-medium">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <h3 className="mt-2 font-semibold">
                                            {step.title}
                                        </h3>
                                        <p className="text-muted-foreground mt-1 text-sm">
                                            {step.description}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    <section className="border-border/60 border-t">
                        <div className="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                            <div className="max-w-2xl">
                                <h2 className="text-3xl font-semibold tracking-tight">
                                    Everything you need, nothing you don't
                                </h2>
                            </div>

                            <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {features.map((feature) => (
                                    <Card key={feature.title}>
                                        <CardContent>
                                            <feature.icon className="text-muted-foreground size-5" />
                                            <h3 className="mt-3 font-semibold">
                                                {feature.title}
                                            </h3>
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                {feature.description}
                                            </p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="border-border/60 border-t">
                        <div className="mx-auto max-w-6xl px-6 py-16 text-center sm:py-24">
                            <h2 className="text-3xl font-semibold tracking-tight">
                                Start monitoring in under a minute
                            </h2>
                            <p className="text-muted-foreground mx-auto mt-2 max-w-xl">
                                Add your first site, pick a check interval, and{' '}
                                {name} takes it from there.
                            </p>
                            <div className="mt-8 flex justify-center">
                                <Button size="lg" asChild>
                                    <Link
                                        href={
                                            auth.user ? dashboard() : register()
                                        }
                                    >
                                        {auth.user
                                            ? 'Go to dashboard'
                                            : 'Create a free account'}
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-border/60 border-t">
                    <div className="text-muted-foreground mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm sm:flex-row">
                        <p>
                            &copy; {new Date().getFullYear()} {name}
                        </p>
                        <nav className="flex items-center gap-4">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="hover:text-foreground"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="hover:text-foreground"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="hover:text-foreground"
                                    >
                                        Register
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </footer>
            </div>
        </>
    );
}
