import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import TextLink from '@/components/text-link';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index, show } from '@/routes/sites';
import type { Endpoint, Site } from '@/types';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import SiteEndpointController, {
    store,
} from '@/actions/App/Http/Controllers/SiteEndpointController';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type Props = {
    site: Site;
};

function formatInterval(minutes: number): string {
    return minutes === 1 ? 'Every minute' : `Every ${minutes} minutes`;
}

function formatUptime(percentage: number | null): string {
    return percentage === null ? '—' : `${percentage}%`;
}

function DeleteEndpointButton({
    domain,
    endpoint,
}: {
    domain: string;
    endpoint: Endpoint;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm">
                    Delete
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Delete this endpoint?</DialogTitle>
                <DialogDescription>
                    {endpoint.location} will stop being checked and its history
                    will be permanently deleted. This cannot be undone.
                </DialogDescription>

                <Form
                    {...SiteEndpointController.destroy.form([
                        domain,
                        endpoint.id,
                    ])}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>

                            <Button
                                variant="destructive"
                                disabled={processing}
                                asChild
                            >
                                <button type="submit">
                                    {processing && <Spinner />}
                                    Delete
                                </button>
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function EndpointsTable({
    domain,
    endpoints,
}: {
    domain: string;
    endpoints: Endpoint[];
}) {
    if (endpoints.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">No endpoints yet.</p>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b text-left">
                        <th className="py-2 pr-4 font-medium">Location</th>
                        <th className="py-2 pr-4 font-medium">Frequency</th>
                        <th className="py-2 pr-4 font-medium">Last check</th>
                        <th className="py-2 pr-4 font-medium">Last status</th>
                        <th className="py-2 pr-4 font-medium">Uptime (24h)</th>
                        <th className="py-2 pr-4 font-medium">
                            Uptime (all time)
                        </th>
                        <th className="py-2 pr-4 font-medium">
                            <span className="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {endpoints.map((endpoint) => (
                        <tr
                            key={endpoint.id}
                            className="border-b last:border-0"
                        >
                            <td className="py-2 pr-4">{endpoint.location}</td>
                            <td className="py-2 pr-4">
                                {formatInterval(endpoint.frequency)}
                            </td>
                            <td className="py-2 pr-4">
                                {endpoint.last_check ?? 'Not checked yet'}
                            </td>
                            <td className="py-2 pr-4">
                                {endpoint.last_status ?? '—'}
                            </td>
                            <td className="py-2 pr-4">
                                {formatUptime(endpoint.uptime_24h)}
                            </td>
                            <td className="py-2 pr-4">
                                {formatUptime(endpoint.uptime)}
                            </td>
                            <td className="py-2 pr-4 text-right">
                                <DeleteEndpointButton
                                    domain={domain}
                                    endpoint={endpoint}
                                />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default function SitesShow({ site }: Props) {
    const title = site.domain ?? site.url;
    const endpoints = site.endpoints?.data ?? [];

    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading title={title} description="Site details" />

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>{title}</CardTitle>
                            <CardDescription>
                                <TextLink href={site.url} className="break-all">
                                    {site.url}
                                </TextLink>
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm">
                            <p>
                                <span className="text-muted-foreground">
                                    Added:
                                </span>{' '}
                                {site.created_at}
                            </p>
                            <p>
                                <span className="text-muted-foreground">
                                    Updated:
                                </span>{' '}
                                {site.updated_at}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Add an endpoint</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm">
                            <Form
                                {...store.form(site.domain as string)}
                                resetOnSuccess
                                className="space-y-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="url">
                                                Endpoint
                                            </Label>
                                            <Input
                                                id="uri"
                                                type="string"
                                                name="uri"
                                                required
                                                autoFocus
                                                placeholder="eg: /home"
                                            />
                                            <InputError message={errors.uri} />
                                        </div>

                                        <div className="grid w-full gap-2">
                                            <Label htmlFor="interval">
                                                Interval
                                            </Label>
                                            <Select name="interval">
                                                <SelectTrigger
                                                    id="interval"
                                                    className="w-full"
                                                >
                                                    <SelectValue placeholder="Select an interval" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="1">
                                                        1 minute
                                                    </SelectItem>
                                                    <SelectItem value="15">
                                                        15 minutes
                                                    </SelectItem>
                                                    <SelectItem value="30">
                                                        30 minutes
                                                    </SelectItem>
                                                    <SelectItem value="60">
                                                        60 minutes
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.interval}
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Add endpoint
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Endpoints</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <EndpointsTable
                            domain={site.domain as string}
                            endpoints={endpoints}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SitesShow.layout = ({ site }: Props) => ({
    breadcrumbs: [
        {
            title: 'Sites',
            href: index(),
        },
        {
            title: site.domain ?? site.url,
            href: show(site.domain ?? site.url),
        },
    ],
});
