import {createAction} from 'typesafe-actions';
import {ILink} from '../Link';

export const EditorWasOpened = createAction(
    'http://sitegeist.de/Sitegeist.Archaeopteryx/EditorWasOpened',
    (value: null | ILink) => value
)();

export const EditorWasDismissed = createAction(
    'http://sitegeist.de/Sitegeist.Archaeopteryx/EditorWasDismissed'
)();

export const ValueWasUpdated = createAction(
    'http://sitegeist.de/Sitegeist.Archaeopteryx/ValueWasUpdated',
    (value: Partial<ILink>) => value
)();

export const ValueWasCleared = createAction(
    'http://sitegeist.de/Sitegeist.Archaeopteryx/ValueWasCleared'
)();

export const ValueWasApplied = createAction(
    'http://sitegeist.de/Sitegeist.Archaeopteryx/ValueWasApplied',
    (value: null | ILink) => value
)();