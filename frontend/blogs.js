import Baz from 'bazooka';
import {searchBlogs} from 'app/blog';

Baz.register({
    'blogs_search': searchBlogs
});


Baz.refresh();