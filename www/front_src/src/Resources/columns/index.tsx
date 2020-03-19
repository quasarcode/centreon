import React from 'react';

import { Grid, Typography, makeStyles, IconButton } from '@material-ui/core';
import IconAcknowledge from '@material-ui/icons/Person';

import { TABLE_COLUMN_TYPES, StatusChip, StatusCode } from '@centreon/ui';

import {
  labelResources,
  labelStatus,
  labelDuration,
  labelTries,
  labelInformation,
  labelState,
  labelLastCheck,
  labelAcknowledge,
} from '../translatedLabels';
import { Resource } from '../models';
import StateColumn from './State';
import GraphColumn from './Graph';

const useStyles = makeStyles((theme) => ({
  resourceDetailsCell: {
    padding: theme.spacing(0.5),
  },
}));

export interface ColumnProps {
  row: Resource;
  isRowSelected: boolean;
  isHovered: boolean;
  style;
  onClick;
}

const SeverityColumn = ({ row }: ColumnProps): JSX.Element | undefined => {
  return (
    row.severity && (
      <StatusChip
        label={row.severity.level.toString()}
        statusCode={StatusCode.None}
      />
    )
  );
};

type StatusColumnProps = {
  actions;
} & Pick<ColumnProps, 'row'>;

const StatusColumnOnHover = ({
  actions,
  row,
}: StatusColumnProps): JSX.Element => {
  return (
    <Grid container spacing={1} alignItems="center">
      <Grid item>
        <IconButton
          size="small"
          color="primary"
          onClick={(): void => actions.onAcknowledge(row)}
          aria-label={`${labelAcknowledge} ${row.name}`}
        >
          <IconAcknowledge />
        </IconButton>
      </Grid>
      <Grid item>
        <StatusChip label={row.status.name[0]} statusCode={row.status.code} />
      </Grid>
    </Grid>
  );
};

const StatusColumn = (actions) => ({
  row,
  isHovered,
}: ColumnProps): JSX.Element => {
  return isHovered ? (
    <StatusColumnOnHover actions={actions} row={row} />
  ) : (
    <StatusChip
      style={{ width: 120 }}
      label={row.status.name}
      statusCode={row.status.code}
    />
  );
};

const ResourcesColumn = ({ row }: ColumnProps): JSX.Element => {
  const classes = useStyles();

  return (
    <Grid container spacing={1} className={classes.resourceDetailsCell}>
      <Grid item>
        {row.icon ? (
          <img src={row.icon.url} alt={row.icon.name} width={21} height={21} />
        ) : (
          <StatusChip label={row.short_type} statusCode={StatusCode.None} />
        )}
      </Grid>
      <Grid item>
        <Typography>{row.name}</Typography>
      </Grid>
      {row.parent && (
        <Grid container spacing={1}>
          <Grid item xs={1} />
          <Grid item>
            <StatusChip statusCode={row.parent?.status?.code || 0} />
          </Grid>
          <Grid item>{row.parent.name}</Grid>
        </Grid>
      )}
    </Grid>
  );
};

const getColumns = (actions) => [
  {
    id: 'severity',
    label: 'S',
    type: TABLE_COLUMN_TYPES.component,
    Component: SeverityColumn,
    clickable: false,
    sortable: false,
  },
  {
    id: 'status',
    label: labelStatus,
    type: TABLE_COLUMN_TYPES.component,
    Component: StatusColumn(actions),
    clickable: false,
    sortable: false,
    width: 125,
  },
  {
    id: 'resources',
    label: labelResources,
    type: TABLE_COLUMN_TYPES.component,
    Component: ResourcesColumn,
    clickable: false,
    sortable: false,
  },
  {
    id: 'graph',
    label: '',
    type: TABLE_COLUMN_TYPES.component,
    Component: GraphColumn,
    clickable: false,
    sortable: false,
    width: 50,
  },
  {
    id: 'duration',
    label: labelDuration,
    type: TABLE_COLUMN_TYPES.string,
    getFormattedString: ({ duration }): string => duration,
  },
  {
    id: 'tries',
    label: labelTries,
    type: TABLE_COLUMN_TYPES.string,
    getFormattedString: ({ tries }): string => tries,
  },
  {
    id: 'last_check',
    label: labelLastCheck,
    type: TABLE_COLUMN_TYPES.string,
    getFormattedString: ({ last_check }): string => last_check,
  },
  {
    id: 'information',
    label: labelInformation,
    type: TABLE_COLUMN_TYPES.string,
    getFormattedString: ({ information }): string => information,
  },
  {
    id: 'state',
    label: labelState,
    type: TABLE_COLUMN_TYPES.component,
    Component: StateColumn,
    clickable: false,
    sortable: false,
    width: 80,
  },
];

export default getColumns;
