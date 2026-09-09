import { Form, Head } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/SiteController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Form
                    {...store.form()}
                    resetOnSuccess
                    className="max-w-xl space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="url">Site URL</Label>
                                <Input
                                    id="url"
                                    type="url"
                                    name="url"
                                    required
                                    autoFocus
                                    placeholder="https://example.com"
                                />
                                <InputError message={errors.url} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Add site
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
