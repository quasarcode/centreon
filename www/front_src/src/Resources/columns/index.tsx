import React from 'react';

import { Grid, Typography, Avatar, makeStyles, fade } from '@material-ui/core';
import { Person as IconAcknowledged } from '@material-ui/icons';

import { TABLE_COLUMN_TYPES, StatusChip, StatusCode } from '@centreon/ui';

import {
  labelResources,
  labelStatus,
  labelDuration,
  labelTries,
} from '../translatedLabels';

import IconDowntime from './icons/Downtime';

interface ColumnProps {
  row;
  Cell: ({ children, width }: { children; width? }) => JSX.Element;
  isRowSelected: boolean;
  style;
  onClick;
}

const SeverityColumn = ({ Cell }: ColumnProps): JSX.Element => {
  return (
    <Cell width={50}>
      <StatusChip label="1" statusCode={StatusCode.None} />
    </Cell>
  );
};

const StatusColumn = ({ Cell, row }: ColumnProps): JSX.Element => {
  return (
    <Cell width={150}>
      <StatusChip label={row.status.name} statusCode={row.status.code} />
    </Cell>
  );
};

const ResourcesColumn = ({ Cell, row }: ColumnProps): JSX.Element => {
  return (
    <Cell>
      <Grid container alignItems="center" justify="center">
        <Grid item xs={2}>
          {row.icon ? (
            <img
              src={row.icon.url}
              alt={row.icon.name}
              width={21}
              height={21}
            />
          ) : (
            <StatusChip label={row.short_name} statusCode={StatusCode.None} />
          )}
        </Grid>
        <Grid item xs={10}>
          <Typography>{row.name}</Typography>
        </Grid>
        {row.parent && (
          <>
            <Grid item xs={1} />
            <Grid item xs={1}>
              <StatusChip statusCode={StatusCode.UpOrOk} />
            </Grid>
            <Grid item xs={10}>
              {row.parent.name}
            </Grid>
          </>
        )}
      </Grid>
    </Cell>
  );
};

const useStateChipStyles = makeStyles((theme) => ({
  stateChip: {
    width: theme.spacing(4),
    height: theme.spacing(4),
  },
  acknowledged: {
    backgroundColor: fade('#AE9500', 0.1),
    color: '#AE9500',
  },
  downtime: {
    backgroundColor: fade('#C117FF', 0.1),
    color: '#C117FF',
  },
}));

const DowntimeChip = (): JSX.Element => {
  const classes = useStateChipStyles();

  return (
    <Avatar className={`${classes.stateChip} ${classes.downtime}`}>
      <IconDowntime />
    </Avatar>
  );
};

const AcknowledgedChip = (): JSX.Element => {
  const classes = useStateChipStyles();

  return (
    <Avatar className={`${classes.stateChip} ${classes.acknowledged}`}>
      <IconAcknowledged />
    </Avatar>
  );
};

const StateColumn = ({ Cell, row }: ColumnProps): JSX.Element => {
  return (
    <Cell width={80}>
      <Grid container spacing={1}>
        {row.in_downtime && (
          <Grid item>
            <DowntimeChip />
          </Grid>
        )}
        {row.acknowledged && (
          <Grid item>
            <AcknowledgedChip />
          </Grid>
        )}
      </Grid>
    </Cell>
  );
};

const columns = [
  {
    id: 'severity',
    label: 'S',
    type: TABLE_COLUMN_TYPES.component,
    Component: SeverityColumn,
    clickable: false,
  },
  {
    id: 'status',
    label: labelStatus,
    type: TABLE_COLUMN_TYPES.component,
    Component: StatusColumn,
    clickable: false,
  },
  {
    id: 'resources',
    label: labelResources,
    type: TABLE_COLUMN_TYPES.component,
    Component: ResourcesColumn,
    clickable: false,
  },
  {
    id: 'duration',
    label: labelDuration,
    type: TABLE_COLUMN_TYPES.string,
    getFormattedString: ({ last_status_change }): string => last_status_change,
  },
  {
    id: 'tries',
    label: labelTries,
    type: TABLE_COLUMN_TYPES.string,
    getFormattedString: ({ tries }): string => tries,
  },
  {
    id: 'lastCheck',
    label: 'Last check',
    type: TABLE_COLUMN_TYPES.string,
    getFormattedString: ({ last_check }): string => last_check,
  },
  {
    id: 'information',
    label: 'Information',
    type: TABLE_COLUMN_TYPES.string,
    getFormattedString: ({ information }): string => information,
  },
  {
    id: 'state',
    label: 'State',
    type: TABLE_COLUMN_TYPES.component,
    Component: StateColumn,
    clickable: false,
  },
];

export default columns;