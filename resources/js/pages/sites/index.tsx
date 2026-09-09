import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/sites';
import type { Site } from '@/types';

type Props = {
    sites: {
        data: Site[];
    };
};

export default function SitesIndex({ sites }: Props) {
    return (
        <>
            <Head title="Sites" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Sites"
                    description="The sites you are monitoring"
                />

                {sites.data.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>No sites yet</CardTitle>
                            <CardDescription>
                                Add a site from the dashboard to start
                                monitoring it.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <Link href={dashboard()}>Go to dashboard</Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <ul className="grid gap-4">
                        {sites.data.map((site) => (
                            <li key={site.id}>
                                <Link
                                    href={show(site.domain ?? site.url)}
                                    prefetch
                                    className="block"
                                >
                                    <Card className="hover:bg-accent/50 transition-colors">
                                        <CardHeader>
                                            <CardTitle>
                                                {site.domain ?? site.url}
                                            </CardTitle>
                                            <CardDescription>
                                                {site.url}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="text-muted-foreground text-sm">
                                                Added {site.created_at}
                                            </p>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

SitesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Sites',
            href: index(),
        },
    ],
};
