import { h } from 'preact';
import { Link } from 'preact-router';

export default function Home({ conferences }) {
    // Защита от undefined
    const safeConferences = conferences || [];
    
    if (safeConferences.length === 0) {
        return <div className="p-3 text-center">No conferences yet</div>;
    }

    return (
        <div className="p-3">
            {safeConferences.map((conference) => (
                <div key={conference.id} className="card border shadow-sm lift mb-3">
                    <div className="card-body">
                        <div className="card-title">
                            <h4 className="font-weight-light">
                                {conference.city} {conference.year}
                            </h4>
                        </div>
                        <Link 
                            className="btn btn-sm btn-primary stretched-link" 
                            href={`/conference/${conference.slug}`}
                        >
                            View
                        </Link>
                    </div>
                </div>
            ))}
        </div>
    );
}