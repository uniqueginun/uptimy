import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';

const sections = [
    {
        title: '1. Acceptance of terms',
        body: 'By creating an account or using this service, you agree to be bound by these Terms and Conditions. If you do not agree, do not register for or use the service.',
    },
    {
        title: '2. The service',
        body: 'This service lets you register sites and endpoints so their availability can be checked on a schedule you choose. Checks are performed on a best-effort basis and are not a guarantee of detection speed, accuracy, or continuous availability of the monitoring service itself.',
    },
    {
        title: '3. Your account',
        body: 'You are responsible for maintaining the confidentiality of your account credentials and for all activity under your account. You must provide accurate information when registering and keep it up to date.',
    },
    {
        title: '4. Acceptable use',
        body: 'You may only add sites and endpoints that you own or are authorized to monitor. You may not use the service to probe, scan, or send traffic to systems you do not have permission to access, or in any way that disrupts or overburdens the service.',
    },
    {
        title: '5. No warranty',
        body: 'The service is provided "as is" and "as available", without warranties of any kind, express or implied, including fitness for a particular purpose or uninterrupted operation.',
    },
    {
        title: '6. Limitation of liability',
        body: 'To the fullest extent permitted by law, the service and its operators are not liable for any indirect, incidental, or consequential damages arising from your use of, or inability to use, the service — including missed or delayed outage notifications.',
    },
    {
        title: '7. Termination',
        body: 'You may stop using the service and delete your account at any time. We may suspend or terminate accounts that violate these terms.',
    },
    {
        title: '8. Changes to these terms',
        body: 'These terms may be updated from time to time. Continued use of the service after a change is posted constitutes acceptance of the updated terms.',
    },
];

export default function Terms() {
    const { name } = usePage().props;

    return (
        <>
            <Head title="Terms and Conditions" />
            <div className="dark bg-background text-foreground min-h-screen">
                <header className="border-border/60 border-b">
                    <div className="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
                        <Link href={home()} className="flex items-center gap-2">
                            <div className="bg-primary text-primary-foreground flex size-8 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-5" />
                            </div>
                            <span className="text-lg font-semibold">
                                {name}
                            </span>
                        </Link>

                        <Link
                            href={home()}
                            className="text-muted-foreground hover:text-foreground flex items-center gap-1.5 text-sm"
                        >
                            <ArrowLeft className="size-4" />
                            Back
                        </Link>
                    </div>
                </header>

                <main className="mx-auto max-w-3xl px-6 py-16">
                    <h1 className="text-3xl font-semibold tracking-tight">
                        Terms and Conditions
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Last updated {new Date().getFullYear()}
                    </p>

                    <div className="mt-10 flex flex-col gap-8">
                        {sections.map((section) => (
                            <section key={section.title}>
                                <h2 className="font-semibold">
                                    {section.title}
                                </h2>
                                <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                                    {section.body}
                                </p>
                            </section>
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
